<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\DatabaseProvision\SessionDatabaseTargetResolver;
use NeNeSuite\InstallManifest\InstallManifestApp;
use NeNeSuite\InstallManifest\InstallManifestFactory;
use NeNeSuite\InstallManifest\InstallManifestRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use NeNeSuite\SuiteEnv\SuiteAppUrlReaderInterface;

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
        private SuiteAppUrlReaderInterface $urls,
        private SessionDatabaseTargetResolver $databaseTargets,
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

        // Prefer the org external_id stamped onto the session by the installer bootstrap
        // (the minted organizations.external_id); fall back to the injected value for the
        // wizard/HTTP path, which never stamps the session (milestone A4).
        $orgExternalId = $session->orgExternalId ?? $this->orgExternalId;

        $manifest = $this->manifestFactory->create(
            $this->suiteId,
            $orgExternalId,
            $session->orgDisplayName,
            $session->disclaimerAcceptedAt,
            $this->manifestApps($session),
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
        $completed = $session->withCompleted($manifest->id, $orgExternalId, $now, $now);

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

    /**
     * Builds manifest apps[] for selected apps that have a configured public URL.
     * Apps without a URL are omitted (their entry is added once the env is wired).
     * Each app's database target is resolved with the session in context, so an
     * operator's per-app override (ADR 0022 mode A) is honoured in the manifest.
     *
     * @return list<InstallManifestApp>
     */
    private function manifestApps(InstallSession $session): array
    {
        $apps = [];
        foreach ($session->selectedApps as $catalogId) {
            $publicUrl = $this->urls->publicUrl($catalogId);
            if ($publicUrl === null) {
                continue;
            }

            $target = $this->databaseTargets->resolve($session, $catalogId);
            $apps[] = new InstallManifestApp(
                $catalogId,
                $publicUrl,
                $target->databaseName,
                $target->mode,
                $target->server,
            );
        }

        return $apps;
    }
}
