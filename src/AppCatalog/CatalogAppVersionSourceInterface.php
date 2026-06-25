<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use DateTimeImmutable;

/**
 * Supplies the version mirror per catalog id (ADR 0013 §4 — "mirror, not originate"). Best-effort:
 * an empty map means no version data is available (e.g. Origin unconfigured), and the catalog read
 * never fails because of it.
 */
interface CatalogAppVersionSourceInterface
{
    /**
     * @return array<string, CatalogAppVersions> keyed by catalog id (missing key = unknown)
     */
    public function versions(DateTimeImmutable $now): array;
}
