<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use NeNeSuite\AppCatalog\AppDependencyResolver;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Replaces an in-progress session's selected apps with the dependency-resolved
 * set and records the `app_selection.changed` audit event (before prior
 * selection → after resolved selection) per docs/explanation/audit-trail.md §4.
 */
final readonly class UpdateAppSelectionUseCase implements UpdateAppSelectionUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private CatalogAppRepositoryInterface $catalog,
        private AppDependencyResolver $resolver,
        private SuiteAuditRecorderInterface $audit,
    ) {
    }

    public function execute(UpdateAppSelectionInput $input): UpdateAppSelectionOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        if ($session->status !== InstallSessionStatus::InProgress) {
            throw new InstallSessionConflictException($session->id, $session->status);
        }

        $resolved = $this->resolver->resolve($this->catalog->load(), $input->selectedApps);

        $before = ['selectedApps' => $session->selectedApps];
        $updated = $session->withSelectedApps($resolved, gmdate('Y-m-d\TH:i:s\Z'));

        $this->sessions->update($updated);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $session->suiteId,
            action: 'app_selection.changed',
            entityType: 'app_selection',
            entityId: $session->id,
            beforeJson: $before,
            afterJson: [
                'requested' => $input->selectedApps,
                'selectedApps' => $resolved,
            ],
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
        ));

        return new UpdateAppSelectionOutput($updated);
    }
}
