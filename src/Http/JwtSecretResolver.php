<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

use RuntimeException;

/**
 * Resolves the apex JWT signing/verification secret, failing closed.
 *
 * `NENE_SUITE_JWT_SECRET` (written by the installer) is used when set. When it is
 * unset, the built-in development secret is permitted ONLY if the operator has
 * explicitly opted in with `NENE_SUITE_ALLOW_DEV_SECRET` (development / local use);
 * otherwise resolution throws so a misconfigured deployment never signs apex
 * sessions with a public, guessable secret. There is no grace period — the silent
 * fallback was removed outright (milestone A1.5).
 */
final readonly class JwtSecretResolver
{
    /** Built-in secret, usable only behind the explicit `NENE_SUITE_ALLOW_DEV_SECRET` opt-in. */
    public const DEV_JWT_SECRET = 'nene-suite-dev-secret';

    /** Accepted truthy spellings for the dev opt-in (strict — not "any non-empty value"). */
    private const DEV_OPT_IN_VALUES = ['1', 'true', 'yes'];

    public function __construct(
        private string $configuredSecret,
        private string $devSecretOptIn,
    ) {
    }

    public function resolve(): string
    {
        if ($this->configuredSecret !== '') {
            return $this->configuredSecret;
        }

        if (in_array(strtolower(trim($this->devSecretOptIn)), self::DEV_OPT_IN_VALUES, true)) {
            return self::DEV_JWT_SECRET;
        }

        throw new RuntimeException(
            'NENE_SUITE_JWT_SECRET is not configured. Set it to a random 32+ byte secret '
            . '(the installer writes one automatically), or set NENE_SUITE_ALLOW_DEV_SECRET=1 to '
            . 'permit the built-in development secret (development / local only — never in production or staging).',
        );
    }
}
