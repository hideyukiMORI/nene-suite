<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseTargets;

use Nene2\Validation\ValidationException;
use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\DatabaseTargets\SetDatabaseTargetsInput;
use NeNeSuite\DatabaseTargets\SetDatabaseTargetsUseCase;
use NeNeSuite\InstallSession\AppDatabaseTargetSelection;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class SetDatabaseTargetsUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    public function testStoresTargetsAndAudits(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($sessions, $recorder)->execute(new SetDatabaseTargetsInput(
            self::SESSION_ID,
            [new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Adopt, 'legacy-db.internal', 'invoice_prod')],
        ));

        $stored = $output->session->databaseTargets;
        self::assertCount(1, $stored);
        self::assertSame('nene-invoice', $stored[0]->catalogId);
        self::assertSame(DatabaseTargetMode::Adopt, $stored[0]->mode);
        self::assertSame('legacy-db.internal', $stored[0]->server);
        self::assertSame('invoice_prod', $stored[0]->name);

        // Persisted to the repository, not just returned.
        $persisted = $sessions->findById(self::SESSION_ID);
        self::assertNotNull($persisted);
        self::assertCount(1, $persisted->databaseTargets);

        $command = $recorder->lastCommand();
        self::assertNotNull($command);
        self::assertSame('database_targets.configured', $command->action);
        self::assertSame('app_database', $command->entityType);
        self::assertSame(self::SESSION_ID, $command->entityId);
        self::assertSame(['targets' => []], $command->beforeJson);
        self::assertNotNull($command->afterJson);
        self::assertSame([
            ['catalog_id' => 'nene-invoice', 'mode' => 'adopt', 'server' => 'legacy-db.internal', 'name' => 'invoice_prod'],
        ], $command->afterJson['targets']);
    }

    public function testRejectsTargetForUnselectedApp(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));

        $this->expectException(ValidationException::class);

        $this->useCase($sessions)->execute(new SetDatabaseTargetsInput(
            self::SESSION_ID,
            [new AppDatabaseTargetSelection('nene-records', DatabaseTargetMode::Provision, null, null)],
        ));
    }

    public function testRejectsProvisionOnExternalServer(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));

        $this->expectException(ValidationException::class);

        $this->useCase($sessions)->execute(new SetDatabaseTargetsInput(
            self::SESSION_ID,
            [new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Provision, 'other-db.internal', null)],
        ));
    }

    public function testRejectsDuplicateCatalogId(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));

        $this->expectException(ValidationException::class);

        $this->useCase($sessions)->execute(new SetDatabaseTargetsInput(
            self::SESSION_ID,
            [
                new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Provision, null, null),
                new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Adopt, null, 'invoice_prod'),
            ],
        ));
    }

    public function testThrowsNotFoundWhenSessionMissing(): void
    {
        $this->expectException(InstallSessionNotFoundException::class);

        $this->useCase(new InMemoryInstallSessionRepository())
            ->execute(new SetDatabaseTargetsInput(self::SESSION_ID, []));
    }

    public function testThrowsConflictWhenSessionNotInProgress(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::Completed));

        $this->expectException(InstallSessionConflictException::class);

        $this->useCase($sessions)->execute(new SetDatabaseTargetsInput(self::SESSION_ID, []));
    }

    private function useCase(
        InMemoryInstallSessionRepository $sessions,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): SetDatabaseTargetsUseCase {
        return new SetDatabaseTargetsUseCase(
            $sessions,
            new DatabaseTargetFactory(new AppDatabaseNamer()),
            $recorder ?? new RecordingSuiteAuditRecorder(),
        );
    }

    private function session(InstallSessionStatus $status): InstallSession
    {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: self::SUITE_ID,
            status: $status,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice', 'nene-clear'],
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
