<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

use Nene2\Auth\GuardedJwtSecretResolver;
use Nene2\Auth\JwtSecretException;
use Nene2\Config\AppEnvironment;

/**
 * Resolves the apex JWT signing/verification secret, failing closed.
 *
 * Thin adapter over the framework-standard {@see GuardedJwtSecretResolver}
 * (ADR 0009 public API): it keeps the suite-specific environment variable names
 * (`NENE_SUITE_JWT_SECRET` / `NENE_SUITE_ALLOW_DEV_SECRET`) and the strict
 * opt-in spelling parse, while inheriting the framework's hybrid policy —
 * a configured secret always wins, the built-in development secret is usable
 * only behind the explicit opt-in, and in production the opt-in is intentionally
 * ignored so a misconfigured deployment can never sign apex sessions with a
 * public, guessable secret.
 */
final readonly class JwtSecretResolver
{
    /**
     * Development-only secret, injected into {@see GuardedJwtSecretResolver}'s
     * `devSecret` slot. Usable only behind the explicit
     * `NENE_SUITE_ALLOW_DEV_SECRET` opt-in and never in production.
     */
    public const DEV_JWT_SECRET = 'nene-suite-dev-secret';

    /** Accepted truthy spellings for the dev opt-in (strict — not "any non-empty value"). */
    private const DEV_OPT_IN_VALUES = ['1', 'true', 'yes'];

    public function __construct(
        private string $configuredSecret,
        private string $devSecretOptIn,
        private AppEnvironment $environment,
    ) {
    }

    /**
     * @throws JwtSecretException when no secret can be resolved safely.
     */
    public function resolve(): string
    {
        return (new GuardedJwtSecretResolver(
            $this->configuredSecret,
            $this->environment,
            in_array(strtolower(trim($this->devSecretOptIn)), self::DEV_OPT_IN_VALUES, true),
            devSecret: self::DEV_JWT_SECRET,
            secretEnvName: 'NENE_SUITE_JWT_SECRET',
            optInEnvName: 'NENE_SUITE_ALLOW_DEV_SECRET',
        ))->resolve();
    }
}
