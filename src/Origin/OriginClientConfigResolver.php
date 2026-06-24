<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Resolves {@see OriginClientConfig} from the environment. `NENE_ORIGIN_URL` is
 * portfolio-neutral (no `NENE_SUITE_` prefix — terminology §4.3); unset means the
 * Origin client is disabled. Read from `$_SERVER` / `$_ENV` to match the other
 * suite env resolvers (e.g. ControlDatabaseConfigResolver).
 */
final readonly class OriginClientConfigResolver
{
    private const ENV_VAR = 'NENE_ORIGIN_URL';

    private const DEFAULT_TIMEOUT_SECONDS = 10;

    public function resolve(): OriginClientConfig
    {
        $url = $_SERVER[self::ENV_VAR] ?? $_ENV[self::ENV_VAR] ?? null;
        $baseUrl = is_string($url) ? rtrim($url, '/') : '';

        return new OriginClientConfig($baseUrl, self::DEFAULT_TIMEOUT_SECONDS);
    }
}
