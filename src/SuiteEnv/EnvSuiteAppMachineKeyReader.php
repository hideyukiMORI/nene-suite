<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

/**
 * Reads per-app machine API keys from the suite environment variable
 * `NENE_SUITE_APP_{SNAKE}_MACHINE_KEY`, where `{SNAKE}` is the catalog id with hyphens replaced by
 * underscores and upper-cased (terminology §4.1) — e.g. `nene-invoice` →
 * `NENE_SUITE_APP_NENE_INVOICE_MACHINE_KEY`. The value equals the sibling's own
 * `NENE2_MACHINE_API_KEY`; the suite sends it as `X-NENE2-API-Key` to read the sibling's auth-gated
 * `/machine/health`. Reads `$_SERVER`/`$_ENV`, the same source NENE2's ConfigLoader populates from
 * `.env` — and the same pattern as {@see EnvSuiteAppUrlReader}.
 */
final readonly class EnvSuiteAppMachineKeyReader implements SuiteAppMachineKeyReaderInterface
{
    public function machineApiKey(string $catalogId): ?string
    {
        $snake = strtoupper(str_replace('-', '_', $catalogId));
        $key = "NENE_SUITE_APP_{$snake}_MACHINE_KEY";

        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
