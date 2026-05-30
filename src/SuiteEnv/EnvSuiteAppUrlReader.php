<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

/**
 * Reads per-app public URLs from the suite environment variable
 * `NENE_SUITE_APP_{SNAKE}_URL`, where `{SNAKE}` is the catalog id with hyphens
 * replaced by underscores and upper-cased (terminology §4.1) — e.g.
 * `nene-invoice` → `NENE_SUITE_APP_NENE_INVOICE_URL`. Reads `$_SERVER`/`$_ENV`,
 * the same source NENE2's ConfigLoader populates from `.env`.
 */
final readonly class EnvSuiteAppUrlReader implements SuiteAppUrlReaderInterface
{
    public function publicUrl(string $catalogId): ?string
    {
        $snake = strtoupper(str_replace('-', '_', $catalogId));
        $key = "NENE_SUITE_APP_{$snake}_URL";

        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
