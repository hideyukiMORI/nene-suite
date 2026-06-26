<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Resolves each selected app's database target (ADR 0021) and acts on it:
 * - `provision` → `CREATE`s the database on the suite server and records `database.provisioned`.
 * - `adopt` → registers an existing database **without any DDL/DML** and records `database.adopted`.
 *
 * No passwords or connection DSNs appear in `after_json` — only catalog id, database
 * name, mode, and the non-secret server label (audit-trail §4).
 */
final readonly class ProvisionAppDatabasesUseCase implements ProvisionAppDatabasesUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private SessionDatabaseTargetResolver $targets,
        private DatabaseProvisionerInterface $provisioner,
        private SuiteAuditRecorderInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(ProvisionAppDatabasesInput $input): ProvisionAppDatabasesOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        $provisioned = [];
        foreach ($session->selectedApps as $catalogId) {
            $target = $this->targets->resolve($session, $catalogId);

            // Adopt is register-only: the suite never creates or mutates an existing
            // database (ADR 0021 §3). Only provision runs DDL, and only on the suite server.
            if ($target->mode === DatabaseTargetMode::Provision) {
                $this->provisioner->provision($target->databaseName);
            }

            $afterJson = [
                'catalog_id'    => $catalogId,
                'database_name' => $target->databaseName,
                'mode'          => $target->mode->value,
            ];

            if ($target->server !== null) {
                $afterJson['server'] = $target->server;
            }

            $this->audit->record(new RecordSuiteAuditEventCommand(
                suiteId: $this->suiteId,
                action: $target->mode === DatabaseTargetMode::Adopt
                    ? 'database.adopted'
                    : 'database.provisioned',
                entityType: 'app_database',
                entityId: $catalogId,
                beforeJson: null,
                afterJson: $afterJson,
                actorUserId: $input->actorUserId,
                source: 'installer_ui',
                installSessionId: $input->installSessionId,
                orgExternalId: $session->orgExternalId,
                requestId: $input->requestId,
            ));

            $provisioned[] = new ProvisionedAppDatabase(
                $catalogId,
                $target->databaseName,
                $target->mode,
                $target->server,
            );
        }

        return new ProvisionAppDatabasesOutput($provisioned);
    }
}
