<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use InvalidArgumentException;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseProvision\ExternalProvisionNotSupportedException;
use NeNeSuite\InstallSession\AppDatabaseTargetSelection;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Stores the operator's per-app database target overrides on an in-progress install
 * session (ADR 0022 mode A, OQ1 — a dedicated op, not an `updateAppSelection` extension).
 *
 * Each selection is validated through the shared {@see DatabaseTargetFactory} — the same
 * guards as the env path — so an unsafe database name or a `provision`-on-external-server
 * choice is rejected with HTTP 422 (ADR 0021 OQ2) instead of failing later at provision
 * time. A target may only name an app already in the session's selection; apps with no
 * entry keep the default (provision). Records `database_targets.configured` (audit-trail §4).
 */
final readonly class SetDatabaseTargetsUseCase implements SetDatabaseTargetsUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private DatabaseTargetFactory $factory,
        private SuiteAuditRecorderInterface $audit,
    ) {
    }

    public function execute(SetDatabaseTargetsInput $input): SetDatabaseTargetsOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        if ($session->status !== InstallSessionStatus::InProgress) {
            throw new InstallSessionConflictException($session->id, $session->status);
        }

        $errors = [];
        $seen = [];

        foreach ($input->targets as $index => $selection) {
            $field = "targets.{$index}";

            if (isset($seen[$selection->catalogId])) {
                $errors[] = new ValidationError($field, "Duplicate database target for '{$selection->catalogId}'.", 'duplicate');

                continue;
            }
            $seen[$selection->catalogId] = true;

            if (!in_array($selection->catalogId, $session->selectedApps, true)) {
                $errors[] = new ValidationError(
                    $field,
                    "App '{$selection->catalogId}' is not in the session's selected apps.",
                    'app_not_selected',
                );

                continue;
            }

            // Validate semantics with the shared factory: safe database name, and
            // external server is adopt-only in the Tier B MVP (ADR 0021 OQ2).
            try {
                $this->factory->create($selection->catalogId, $selection->mode, $selection->server, $selection->name);
            } catch (ExternalProvisionNotSupportedException $exception) {
                $errors[] = new ValidationError($field, $exception->getMessage(), 'external_provision_unsupported');
            } catch (InvalidArgumentException $exception) {
                $errors[] = new ValidationError($field, $exception->getMessage(), 'invalid_database_name');
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $before = ['targets' => $this->auditTargets($session->databaseTargets)];
        $updated = $session->withDatabaseTargets($input->targets, gmdate('Y-m-d\TH:i:s\Z'));

        $this->sessions->update($updated);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $session->suiteId,
            action: 'database_targets.configured',
            entityType: 'app_database',
            entityId: $session->id,
            beforeJson: $before,
            afterJson: ['targets' => $this->auditTargets($updated->databaseTargets)],
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
        ));

        return new SetDatabaseTargetsOutput($updated);
    }

    /**
     * Sanitized per-app target snapshot for audit (no credentials — catalog id, mode,
     * and the non-secret server label / existing database name only).
     *
     * @param list<AppDatabaseTargetSelection> $targets
     *
     * @return list<array<string, string>>
     */
    private function auditTargets(array $targets): array
    {
        $rows = [];

        foreach ($targets as $target) {
            $row = [
                'catalog_id' => $target->catalogId,
                'mode'       => $target->mode->value,
            ];

            if ($target->server !== null) {
                $row['server'] = $target->server;
            }

            if ($target->name !== null) {
                $row['name'] = $target->name;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
