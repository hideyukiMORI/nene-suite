<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteEnv\SuiteAppUrlReaderInterface;

/**
 * Lists the apps installed through the suite (R-06) for the apex launcher: the
 * apps selected by the latest completed install session, enriched with catalog
 * name and SSOT role and the public URL from the suite environment. Apps without
 * a configured public URL are omitted (the launcher only lists reachable apps).
 * Per-app `databaseName` stays null until the provisioning slice records it.
 */
final readonly class ListInstalledAppsUseCase implements ListInstalledAppsUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private CatalogAppRepositoryInterface $catalog,
        private SuiteAppUrlReaderInterface $urls,
    ) {
    }

    public function execute(): ListInstalledAppsOutput
    {
        $session = $this->sessions->findLatestCompleted();

        if ($session === null) {
            return new ListInstalledAppsOutput([]);
        }

        $byId = [];
        foreach ($this->catalog->load()->apps as $app) {
            $byId[$app->id] = $app;
        }

        $installed = [];
        foreach ($session->selectedApps as $catalogId) {
            $app = $byId[$catalogId] ?? null;
            if (!$app instanceof CatalogApp) {
                continue;
            }

            $publicUrl = $this->urls->publicUrl($catalogId);
            if ($publicUrl === null) {
                continue;
            }

            $installed[] = new InstalledApp(
                catalogId: $catalogId,
                name: $app->name,
                publicUrl: $publicUrl,
                databaseName: null,
                ssotRole: SsotRole::fromProvides($app->provides),
            );
        }

        return new ListInstalledAppsOutput($installed);
    }
}
