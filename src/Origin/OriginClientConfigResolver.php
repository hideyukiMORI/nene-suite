<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Resolves {@see OriginClientConfig} from the environment. `NENE_ORIGIN_URL` is portfolio-neutral
 * (no `NENE_SUITE_` prefix — terminology §4.3) and carries the mirrors.md §3 semantics: **unset** =
 * the client's embedded default mirror list, **set** = exactly that one base URL (exclusive
 * override, no fallback to the defaults). Neither turns the client on by itself — the embedded trust
 * anchor still gates that. The anti-rollback floors and the trust-anchor path are build-time pins.
 * Read from `$_SERVER` / `$_ENV` to match the other suite env resolvers (e.g.
 * ControlDatabaseConfigResolver).
 */
final readonly class OriginClientConfigResolver
{
    private const ENV_BASE_URL = 'NENE_ORIGIN_URL';

    private const ENV_ROOT_VERSION_FLOOR = 'NENE_ORIGIN_ROOT_VERSION_FLOOR';

    private const ENV_GEN_FLOOR = 'NENE_ORIGIN_GEN_FLOOR';

    private const ENV_TRUST_ANCHOR_PATH = 'NENE_ORIGIN_TRUST_ANCHOR_PATH';

    private const DEFAULT_TIMEOUT_SECONDS = 10;

    public function resolve(): OriginClientConfig
    {
        $url = self::env(self::ENV_BASE_URL);
        $trustAnchorPath = self::env(self::ENV_TRUST_ANCHOR_PATH);

        return new OriginClientConfig(
            mirrors: $url !== null && trim($url) !== ''
                ? OriginMirrorList::exclusive($url)
                : OriginMirrorList::embeddedDefault(),
            timeoutSeconds: self::DEFAULT_TIMEOUT_SECONDS,
            rootVersionFloor: self::intEnv(self::ENV_ROOT_VERSION_FLOOR, 1),
            genFloor: self::intEnv(self::ENV_GEN_FLOOR, 1),
            trustAnchorPath: $trustAnchorPath !== null && $trustAnchorPath !== '' ? $trustAnchorPath : null,
        );
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    private static function intEnv(string $name, int $default): int
    {
        $value = self::env($name);

        return $value !== null && ctype_digit($value) ? (int) $value : $default;
    }
}
