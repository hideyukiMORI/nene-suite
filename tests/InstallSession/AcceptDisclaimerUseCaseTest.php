<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\AcceptDisclaimerInput;
use NeNeSuite\InstallSession\AcceptDisclaimerUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class AcceptDisclaimerUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testAcceptsDisclaimerPersistsAndAudits(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));
        $recorder = new RecordingSuiteAuditRecorder();

        $useCase = new AcceptDisclaimerUseCase($sessions, $recorder);
        $output = $useCase->execute(new AcceptDisclaimerInput(self::SESSION_ID, '2026-05-29', 'operator@example.com'));

        self::assertTrue($output->session->disclaimerAccepted);
        self::assertNotNull($output->session->disclaimerAcceptedAt);
        self::assertTrue($sessions->findById(self::SESSION_ID)?->disclaimerAccepted);

        $command = $recorder->lastCommand();
        self::assertNotNull($command);
        self::assertSame('disclaimer.accepted', $command->action);
        self::assertSame('disclaimer_acknowledgment', $command->entityType);
        self::assertSame('operator@example.com', $command->actorLabel);
        self::assertNull($command->beforeJson);
        self::assertNotNull($command->afterJson);
        self::assertSame('2026-05-29', $command->afterJson['disclaimerVersion']);
    }

    public function testThrowsNotFoundWhenMissing(): void
    {
        $useCase = new AcceptDisclaimerUseCase(new InMemoryInstallSessionRepository(), new RecordingSuiteAuditRecorder());

        $this->expectException(InstallSessionNotFoundException::class);

        $useCase->execute(new AcceptDisclaimerInput(self::SESSION_ID, '2026-05-29'));
    }

    public function testThrowsConflictWhenNotInProgress(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::Failed));

        $useCase = new AcceptDisclaimerUseCase($sessions, new RecordingSuiteAuditRecorder());

        $this->expectException(InstallSessionConflictException::class);

        $useCase->execute(new AcceptDisclaimerInput(self::SESSION_ID, '2026-05-29'));
    }

    private function session(InstallSessionStatus $status): InstallSession
    {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: '01J8XRDEV000000000000000ZA',
            status: $status,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: [],
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: null,
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
        );
    }
}
