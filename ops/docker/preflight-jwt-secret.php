<?php

declare(strict_types=1);

/**
 * Serving-only boot preflight: resolve the apex JWT signing secret at container start so a
 * misconfigured deployment fails LOUDLY (non-zero exit aborts the entrypoint before Apache
 * serves) instead of appearing healthy and only breaking at first login.
 *
 * Invoked from ops/docker/entrypoint.sh on the `apache2-foreground` command ONLY — never
 * during the one-off installer (`php installer/install.php`), which legitimately runs before
 * NENE_SUITE_JWT_SECRET has been written. Resolving the concrete LocalBearerTokenVerifier
 * triggers NeNeSuite\Http\JwtSecretResolver, which throws when the secret is unconfigured and
 * NENE_SUITE_ALLOW_DEV_SECRET is not set (milestone A1.5;
 * docs/explanation/suite-environment-contract.md).
 */

use Nene2\Auth\LocalBearerTokenVerifier;
use NeNeSuite\Http\RuntimeContainerFactory;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
    $container->get(LocalBearerTokenVerifier::class);
} catch (Throwable $exception) {
    // The DI container wraps the factory's exception; walk the chain so the operator sees
    // the actionable root-cause message (which env var to set), not just "could not be resolved".
    $message = $exception->getMessage();
    for ($cause = $exception->getPrevious(); $cause !== null; $cause = $cause->getPrevious()) {
        $message .= ' — ' . $cause->getMessage();
    }

    fwrite(STDERR, '[preflight] FATAL: apex JWT signing secret is not configured: ' . $message . "\n");
    exit(1);
}

echo "[preflight] apex JWT signing secret OK\n";
exit(0);
