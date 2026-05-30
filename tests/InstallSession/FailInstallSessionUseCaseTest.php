<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\FailInstallSessionInput;
use NeNeSuite\InstallSession\FailInstallSessionUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class FailInstallSessionUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testMarksFailedPersistsAndAudits(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));
        $recorder = new RecordingSuiteAuditRecorder();

        $useCase = new FailInstallSessionUseCase($sessions, $recorder);
        $output = $useCase->execute(new FailInstallSessionInput(self::SESSION_ID, 'database_provision_failed', 'Could not create database.'));

        self::assertSame(InstallSessionStatus::Failed, $output->session->status);
        self::assertSame('database_provision_failed', $output->session->failureCode);
        self::assertSame(InstallSessionStatus::Failed, $sessions->findById(self::SESSION_ID)?->status);

        $command = $recorder->lastCommand();
        self::assertNotNull($command);
        self::assertSame('install_session.failed', $command->action);
        self::assertSame('install_session', $command->entityType);
        self::assertNotNull($command->beforeJson);
        self::assertSame('in_progress', $command->beforeJson['status']);
        self::assertNotNull($command->afterJson);
        self::assertSame('failed', $command->afterJson['status']);
        self::assertNotNull($command->metadataJson);
        self::assertSame('database_provision_failed', $command->metadataJson['failure_code']);
        self::assertSame('Could not create database.', $command->metadataJson['reason']);
    }

    public function testThrowsConflictWhenNotInProgress(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::Completed));

        $useCase = new FailInstallSessionUseCase($sessions, new RecordingSuiteAuditRecorder());

        $this->expectException(InstallSessionConflictException::class);

        $useCase->execute(new FailInstallSessionInput(self::SESSION_ID, 'too_late'));
    }

    private function session(InstallSessionStatus $status): InstallSession
    {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: '01J8XRDEV000000000000000ZA',
            status: $status,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice'],
            disclaimerAccepted: true,
            disclaimerAcceptedAt: '2026-05-30T09:50:00Z',
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
