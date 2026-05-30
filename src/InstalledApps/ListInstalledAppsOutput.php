<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

final readonly class ListInstalledAppsOutput
{
    /**
     * @param list<InstalledApp> $apps
     */
    public function __construct(
        public array $apps,
    ) {
    }
}
