<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * Derives the per-app control/sibling database name from a catalog id by the
 * suite convention: hyphens become underscores (e.g. `nene-invoice` →
 * `nene_invoice`). This is the **name** the installer provisions; actual database
 * creation is the Tier B installer's job (no cross-DB SQL here).
 */
final readonly class AppDatabaseNamer
{
    public function databaseName(string $catalogId): string
    {
        return str_replace('-', '_', $catalogId);
    }
}
