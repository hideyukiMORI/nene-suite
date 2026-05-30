<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppSelection;

use NeNeSuite\AppCatalog\AppDependencyResolver;
use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\AppSelection\UpdateAppSelectionInput;
use NeNeSuite\AppSelection\UpdateAppSelectionUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\AppCatalog\InMemoryCatalogAppRepository;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class UpdateAppSelectionUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testResolvesDependenciesPersistsAndAudits(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::InProgress));
        $recorder = new RecordingSuiteAuditRecorder();

        $useCase = new UpdateAppSelectionUseCase(
            $sessions,
            new InMemoryCatalogAppRepository($this->catalog()),
            new AppDependencyResolver(),
            $recorder,
        );

        $output = $useCase->execute(new UpdateAppSelectionInput(self::SESSION_ID, ['nene-clear']));

        self::assertSame(['nene-invoice', 'nene-clear'], $output->session->selectedApps);
        self::assertSame(['nene-invoice', 'nene-clear'], $sessions->findById(self::SESSION_ID)?->selectedApps);

        $command = $recorder->lastCommand();
        self::assertNotNull($command);
        self::assertSame('app_selection.changed', $command->action);
        self::assertSame('app_selection', $command->entityType);
        self::assertSame(self::SESSION_ID, $command->entityId);
        self::assertSame(['selectedApps' => []], $command->beforeJson);
        self::assertNotNull($command->afterJson);
        self::assertSame(['nene-invoice', 'nene-clear'], $command->afterJson['selectedApps']);
        self::assertSame(['nene-clear'], $command->afterJson['requested']);
    }

    public function testThrowsNotFoundWhenSessionMissing(): void
    {
        $useCase = new UpdateAppSelectionUseCase(
            new InMemoryInstallSessionRepository(),
            new InMemoryCatalogAppRepository($this->catalog()),
            new AppDependencyResolver(),
            new RecordingSuiteAuditRecorder(),
        );

        $this->expectException(InstallSessionNotFoundException::class);

        $useCase->execute(new UpdateAppSelectionInput(self::SESSION_ID, []));
    }

    public function testThrowsConflictWhenSessionNotInProgress(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->session(InstallSessionStatus::Completed));

        $useCase = new UpdateAppSelectionUseCase(
            $sessions,
            new InMemoryCatalogAppRepository($this->catalog()),
            new AppDependencyResolver(),
            new RecordingSuiteAuditRecorder(),
        );

        $this->expectException(InstallSessionConflictException::class);

        $useCase->execute(new UpdateAppSelectionInput(self::SESSION_ID, ['nene-invoice']));
    }

    private function catalog(): Catalog
    {
        return new Catalog(1, [
            new CatalogApp('nene-invoice', 'NeNe Invoice', null, 'nene-invoice', 'installable', [], ['billing-api'], '/install/index.php', 'NENE_INVOICE_DB_'),
            new CatalogApp('nene-clear', 'NeNe Clear', null, 'nene-clear', 'installable', ['nene-invoice'], ['reconciliation-api'], '/install/index.php', 'NENE_CLEAR_DB_'),
        ]);
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
