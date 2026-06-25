<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use DateTimeImmutable;
use NeNeSuite\AppCatalog\CatalogAppVersions;
use NeNeSuite\AppCatalog\CatalogAppVersionSourceInterface;

/**
 * Returns a fixed version mirror per catalog id — lets the catalog use case be tested without Origin.
 */
final readonly class FakeCatalogAppVersionSource implements CatalogAppVersionSourceInterface
{
    /** @param array<string, CatalogAppVersions> $versions */
    public function __construct(private array $versions = [])
    {
    }

    public function versions(DateTimeImmutable $now): array
    {
        return $this->versions;
    }
}
