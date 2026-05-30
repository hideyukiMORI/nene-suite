<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\InstallSession\StartInstallSessionInput;
use NeNeSuite\InstallSession\StartInstallSessionUseCase;
use NeNeSuite\Tests\AppCatalog\InMemoryCatalogAppRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class StartInstallSessionUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    public function testCreatesInProgressSessionAndPersistsIt(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $useCase = new StartInstallSessionUseCase(
            $sessions,
            new RecordingSuiteAuditRecorder(),
            new InMemoryCatalogAppRepository(new Catalog(7, [])),
            self::SUITE_ID,
        );

        $output = $useCase->execute(new StartInstallSessionInput(
            tier: InstallTier::B,
            selectedApps: ['nene-invoice'],
            orgDisplayName: 'Example Organization',
        ));

        $session = $output->session;
        self::assertSame(InstallSessionStatus::InProgress, $session->status);
        self::assertSame(InstallTier::B, $session->tier);
        self::assertSame(7, $session->catalogRevision);
        self::assertSame(self::SUITE_ID, $session->suiteId);
        self::assertSame(['nene-invoice'], $session->selectedApps);
        self::assertFalse($session->disclaimerAccepted);
        self::assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $session->id);
        self::assertSame($session, $sessions->findById($session->id));
    }

    public function testRecordsStartedAuditEventWithAfterSnapshot(): void
    {
        $recorder = new RecordingSuiteAuditRecorder();
        $useCase = new StartInstallSessionUseCase(
            new InMemoryInstallSessionRepository(),
            $recorder,
            new InMemoryCatalogAppRepository(new Catalog(1, [])),
            self::SUITE_ID,
        );

        $output = $useCase->execute(new StartInstallSessionInput(
            tier: InstallTier::B,
            requestId: 'req-123',
        ));

        self::assertCount(1, $recorder->commands);
        $command = $recorder->lastCommand();
        self::assertNotNull($command);
        self::assertSame('install_session.started', $command->action);
        self::assertSame('install_session', $command->entityType);
        self::assertSame($output->session->id, $command->entityId);
        self::assertSame($output->session->id, $command->installSessionId);
        self::assertSame('installer_ui', $command->source);
        self::assertSame('req-123', $command->requestId);
        self::assertNull($command->beforeJson);
        self::assertNotNull($command->afterJson);
        self::assertSame('in_progress', $command->afterJson['status']);
        self::assertSame($output->session->id, $command->afterJson['id']);
    }
}
