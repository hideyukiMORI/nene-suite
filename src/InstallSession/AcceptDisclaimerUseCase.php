<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Records operator acceptance of the disclaimer (R-04) and emits the
 * `disclaimer.accepted` audit event (before null → ack snapshot) per
 * docs/explanation/audit-trail.md §4.
 */
final readonly class AcceptDisclaimerUseCase implements AcceptDisclaimerUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private SuiteAuditRecorderInterface $audit,
    ) {
    }

    public function execute(AcceptDisclaimerInput $input): AcceptDisclaimerOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        if ($session->status !== InstallSessionStatus::InProgress) {
            throw new InstallSessionConflictException($session->id, $session->status);
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $updated = $session->withDisclaimerAccepted($now, $now);

        $this->sessions->update($updated);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $session->suiteId,
            action: 'disclaimer.accepted',
            entityType: 'disclaimer_acknowledgment',
            entityId: $session->id,
            beforeJson: null,
            afterJson: [
                'disclaimerVersion' => $input->disclaimerVersion,
                'acceptedAt' => $now,
            ],
            actorLabel: $input->acceptedLabel,
            source: 'installer_ui',
            installSessionId: $session->id,
            requestId: $input->requestId,
        ));

        return new AcceptDisclaimerOutput($updated);
    }
}
