<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

final readonly class ListCatalogAppsOutput
{
    /**
     * @param list<CatalogApp>                  $apps
     * @param array<string, CatalogAppVersions> $versions version mirror keyed by catalog id (missing key = unknown)
     */
    public function __construct(
        public int $version,
        public array $apps,
        public array $versions = [],
    ) {
    }
}
