<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

/**
 * Point-in-time snapshot of catalog/apps.json: a version plus the ordered apps.
 */
final readonly class Catalog
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
