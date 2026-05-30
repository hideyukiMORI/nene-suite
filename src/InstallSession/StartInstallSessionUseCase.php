<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Starts a new install session in status in_progress and records the
 * `install_session.started` audit event (before null → after snapshot) per
 * docs/explanation/audit-trail.md §4.
 */
final readonly class StartInstallSessionUseCase implements StartInstallSessionUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private SuiteAuditRecorderInterface $audit,
        private CatalogAppRepositoryInterface $catalog,
        private string $suiteId,
    ) {
    }

    public function execute(StartInstallSessionInput $input): StartInstallSessionOutput
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $id = (string) new Ulid();

        $session = new InstallSession(
            id: $id,
            suiteId: $this->suiteId,
            status: InstallSessionStatus::InProgress,
            tier: $input->tier,
            catalogRevision: $this->catalog->load()->version,
            selectedApps: $input->selectedApps,
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: $input->orgDisplayName,
            installManifestId: null,
            failureCode: null,
            createdAt: $now,
            updatedAt: $now,
            completedAt: null,
        );

        $this->sessions->save($session);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $this->suiteId,
            action: 'install_session.started',
            entityType: 'install_session',
            entityId: $id,
            beforeJson: null,
            afterJson: InstallSessionView::toArray($session),
            source: 'installer_ui',
            installSessionId: $id,
            requestId: $input->requestId,
        ));

        return new StartInstallSessionOutput($session);
    }
}
