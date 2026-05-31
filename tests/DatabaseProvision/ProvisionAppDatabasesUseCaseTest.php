<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesInput;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class ProvisionAppDatabasesUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const SUITE_ID   = '01J8XRDEV000000000000000ZA';
    private const ORG_ID     = '01J8XRDEV0FED0000000000ZAB';

    public function testProvisionsDatabaseAndAuditsEachApp(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession(['nene-invoice', 'nene-clear']));
        $provisioner = new RecordingDatabaseProvisioner();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($sessions, $provisioner, $recorder)
            ->execute(new ProvisionAppDatabasesInput(self::SESSION_ID));

        self::assertSame(['nene_invoice', 'nene_clear'], $provisioner->provisioned);

        self::assertCount(2, $output->provisioned);
        self::assertSame('nene-invoice', $output->provisioned[0]->catalogId);
        self::assertSame('nene_invoice', $output->provisioned[0]->databaseName);
        self::assertSame('nene-clear', $output->provisioned[1]->catalogId);
        self::assertSame('nene_clear', $output->provisioned[1]->databaseName);

        self::assertCount(2, $recorder->commands);
        $firstEvent = $recorder->commands[0];
        self::assertSame('database.provisioned', $firstEvent->action);
        self::assertSame('app_database', $firstEvent->entityType);
        self::assertSame('nene-invoice', $firstEvent->entityId);
        self::assertNull($firstEvent->beforeJson);
        self::assertSame([
            'catalog_id'    => 'nene-invoice',
            'database_name' => 'nene_invoice',
        ], $firstEvent->afterJson);
        self::assertSame(self::SESSION_ID, $firstEvent->installSessionId);

        $secondEvent = $recorder->commands[1];
        self::assertSame('nene-clear', $secondEvent->entityId);
        self::assertSame('nene_clear', $secondEvent->afterJson['database_name'] ?? null);
    }

    public function testReturnsEmptyWhenNoAppsSelected(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession([]));
        $provisioner = new RecordingDatabaseProvisioner();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($sessions, $provisioner, $recorder)
            ->execute(new ProvisionAppDatabasesInput(self::SESSION_ID));

        self::assertSame([], $provisioner->provisioned);
        self::assertSame([], $output->provisioned);
        self::assertCount(0, $recorder->commands);
    }

    public function testThrowsWhenSessionNotFound(): void
    {
        $this->expectException(InstallSessionNotFoundException::class);

        $this->useCase(new InMemoryInstallSessionRepository())
            ->execute(new ProvisionAppDatabasesInput(self::SESSION_ID));
    }

    private function useCase(
        InMemoryInstallSessionRepository $sessions,
        ?RecordingDatabaseProvisioner $provisioner = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): ProvisionAppDatabasesUseCase {
        return new ProvisionAppDatabasesUseCase(
            sessions: $sessions,
            namer: new AppDatabaseNamer(),
            provisioner: $provisioner ?? new RecordingDatabaseProvisioner(),
            audit: $recorder ?? new RecordingSuiteAuditRecorder(),
            suiteId: self::SUITE_ID,
        );
    }

    /**
     * @param list<string> $selectedApps
     */
    private function completedSession(array $selectedApps): InstallSession
    {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: self::SUITE_ID,
            status: InstallSessionStatus::Completed,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: $selectedApps,
            disclaimerAccepted: true,
            disclaimerAcceptedAt: '2026-05-31T10:00:00Z',
            orgExternalId: self::ORG_ID,
            orgDisplayName: 'Example Organization',
            installManifestId: '01J8XRMANIFEST00000000000A',
            failureCode: null,
            createdAt: '2026-05-31T09:58:00Z',
            updatedAt: '2026-05-31T10:00:00Z',
            completedAt: '2026-05-31T10:00:00Z',
        );
    }
}
