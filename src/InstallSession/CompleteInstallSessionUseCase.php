<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\InstallManifest\InstallManifestFactory;
use NeNeSuite\InstallManifest\InstallManifestRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Finalizes an install session (R-04, R-05, R-08): enforces preconditions,
 * writes the install manifest (ADR 0010), and records `manifest.created` plus
 * `install_session.completed`. The completed `after` snapshot carries
 * `installManifestId` for audit/manifest alignment (audit-trail §8).
 */
final readonly class CompleteInstallSessionUseCase implements CompleteInstallSessionUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private InstallManifestRepositoryInterface $manifests,
        private InstallManifestFactory $manifestFactory,
        private SuiteAuditRecorderInterface $audit,
        private string $suiteId,
        private string $orgExternalId,
    ) {
    }

    public function execute(CompleteInstallSessionInput $input): CompleteInstallSessionOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        if ($session->status !== InstallSessionStatus::InProgress) {
            throw new InstallSessionConflictException($session->id, $session->status);
        }

        if (!$session->disclaimerAccepted) {
            throw new InstallSessionNotReadyException(
                'disclaimer-not-accepted',
                'Disclaimer not accepted',
                'The disclaimer must be accepted before completing installation.',
            );
        }

        if ($session->selectedApps === []) {
            throw new InstallSessionNotReadyException(
                'no-apps-selected',
                'No apps selected',
                'At least one app must be selected before completing installation.',
            );
        }

        $manifest = $this->manifestFactory->create(
            $this->suiteId,
            $this->orgExternalId,
            $session->orgDisplayName,
            $session->disclaimerAcceptedAt,
        );
        $this->manifests->save($manifest);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $this->suiteId,
            action: 'manifest.created',
            entityType: 'install_manifest',
            entityId: $manifest->id,
            beforeJson: null,
            afterJson: $manifest->body,
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
            metadataJson: ['manifest_content_hash' => $manifest->contentHash],
        ));

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $before = InstallSessionView::toArray($session);
        $completed = $session->withCompleted($manifest->id, $this->orgExternalId, $now, $now);

        $this->sessions->update($completed);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $this->suiteId,
            action: 'install_session.completed',
            entityType: 'install_session',
            entityId: $session->id,
            beforeJson: $before,
            afterJson: InstallSessionView::toArray($completed),
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
        ));

        return new CompleteInstallSessionOutput($completed);
    }
}
