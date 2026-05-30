<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Marks an in-progress session as failed and emits the `install_session.failed`
 * audit event (before in-progress → after failed snapshot, with required
 * metadata_json.failure_code) per docs/explanation/audit-trail.md §4.
 */
final readonly class FailInstallSessionUseCase implements FailInstallSessionUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private SuiteAuditRecorderInterface $audit,
    ) {
    }

    public function execute(FailInstallSessionInput $input): FailInstallSessionOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        if ($session->status !== InstallSessionStatus::InProgress) {
            throw new InstallSessionConflictException($session->id, $session->status);
        }

        $before = InstallSessionView::toArray($session);
        $failed = $session->withFailure($input->failureCode, gmdate('Y-m-d\TH:i:s\Z'));

        $this->sessions->update($failed);

        $metadata = ['failure_code' => $input->failureCode];
        if ($input->reason !== null) {
            $metadata['reason'] = $input->reason;
        }

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $session->suiteId,
            action: 'install_session.failed',
            entityType: 'install_session',
            entityId: $session->id,
            beforeJson: $before,
            afterJson: InstallSessionView::toArray($failed),
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
            metadataJson: $metadata,
        ));

        return new FailInstallSessionOutput($failed);
    }
}
