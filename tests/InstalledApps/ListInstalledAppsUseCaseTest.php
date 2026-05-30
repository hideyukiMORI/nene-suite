<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstalledApps;

use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCase;
use NeNeSuite\InstalledApps\SsotRole;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\AppCatalog\InMemoryCatalogAppRepository;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use PHPUnit\Framework\TestCase;

final class ListInstalledAppsUseCaseTest extends TestCase
{
    public function testReturnsEmptyWhenNoCompletedSession(): void
    {
        $useCase = new ListInstalledAppsUseCase(
            new InMemoryInstallSessionRepository(),
            new InMemoryCatalogAppRepository($this->catalog()),
            new FixedSuiteAppUrlReader([]),
        );

        self::assertSame([], $useCase->execute()->apps);
    }

    public function testBuildsInstalledAppsAndOmitsThoseWithoutPublicUrl(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession(['nene-invoice', 'nene-clear']));

        $useCase = new ListInstalledAppsUseCase(
            $sessions,
            new InMemoryCatalogAppRepository($this->catalog()),
            // Only nene-invoice has a configured URL; nene-clear is omitted.
            new FixedSuiteAppUrlReader(['nene-invoice' => 'https://example.com/nene-invoice/']),
        );

        $apps = $useCase->execute()->apps;

        self::assertCount(1, $apps);
        self::assertSame('nene-invoice', $apps[0]->catalogId);
        self::assertSame('NeNe Invoice', $apps[0]->name);
        self::assertSame('https://example.com/nene-invoice/', $apps[0]->publicUrl);
        self::assertNull($apps[0]->databaseName);
        self::assertSame(SsotRole::Billing, $apps[0]->ssotRole);
    }

    public function testMapsSsotRolesFromProvides(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession(['nene-invoice', 'nene-clear']));

        $useCase = new ListInstalledAppsUseCase(
            $sessions,
            new InMemoryCatalogAppRepository($this->catalog()),
            new FixedSuiteAppUrlReader([
                'nene-invoice' => 'https://example.com/nene-invoice/',
                'nene-clear' => 'https://example.com/nene-clear/',
            ]),
        );

        $roles = [];
        foreach ($useCase->execute()->apps as $app) {
            $roles[$app->catalogId] = $app->ssotRole;
        }

        self::assertSame(SsotRole::Billing, $roles['nene-invoice']);
        self::assertSame(SsotRole::ReconciliationEvidence, $roles['nene-clear']);
    }

    private function catalog(): Catalog
    {
        return new Catalog(1, [
            new CatalogApp('nene-invoice', 'NeNe Invoice', null, 'nene-invoice', 'installable', [], ['billing-api'], '/install/index.php', 'NENE_INVOICE_DB_'),
            new CatalogApp('nene-clear', 'NeNe Clear', null, 'nene-clear', 'installable', ['nene-invoice'], ['reconciliation-api'], '/install/index.php', 'NENE_CLEAR_DB_'),
        ]);
    }

    /**
     * @param list<string> $selectedApps
     */
    private function completedSession(array $selectedApps): InstallSession
    {
        return new InstallSession(
            id: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            suiteId: '01J8XRDEV000000000000000ZA',
            status: InstallSessionStatus::Completed,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: $selectedApps,
            disclaimerAccepted: true,
            disclaimerAcceptedAt: '2026-05-30T09:50:00Z',
            orgExternalId: '01J8XRDEV0FED0000000000ZAB',
            orgDisplayName: 'Example Organization',
            installManifestId: '01J8XR9MAQ9V2H7K3N5M0B8DFG',
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:51:30Z',
            completedAt: '2026-05-30T09:51:30Z',
        );
    }
}
