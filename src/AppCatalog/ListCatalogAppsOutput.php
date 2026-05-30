<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

final readonly class ListCatalogAppsOutput
{
    /**
     * @param list<CatalogApp> $apps
     */
    public function __construct(
        public int $version,
        public array $apps,
    ) {
    }
}
