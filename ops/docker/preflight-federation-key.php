<?php

declare(strict_types=1);

/**
 * Serving-only boot preflight (milestone B1.7): in the HOSTED edition, fail closed at container
 * start when the federation signing key is missing/unloadable or its kid does not match the active
 * published key — instead of serving a broken IdP that silently fails all SSO. OSS skips entirely.
 *
 * Invoked from ops/docker/entrypoint.sh on `apache2-foreground` only, after `phinx migrate` (it
 * reads the federation_signing_keys table). Reads the private key from NENE_SUITE_FEDERATION_PRIVATE_KEY
 * or, if unset, from the file named by NENE_SUITE_FEDERATION_PRIVATE_KEY_FILE (secret-mount friendly).
 */

use NeNeSuite\Auth\FederationKeyPreflight;
use NeNeSuite\Auth\FederationKeyPreflightException;
use NeNeSuite\Http\Edition;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Http\RuntimeServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

$edition = $container->get(RuntimeServiceProvider::EDITION);

if (!$edition instanceof Edition || !$edition->isHosted()) {
    echo "[preflight] federation signing key check skipped (edition is not hosted)\n";
    exit(0);
}

$privateKeyPem = trim((string) ($_SERVER['NENE_SUITE_FEDERATION_PRIVATE_KEY'] ?? $_ENV['NENE_SUITE_FEDERATION_PRIVATE_KEY'] ?? ''));

if ($privateKeyPem === '') {
    $file = trim((string) ($_SERVER['NENE_SUITE_FEDERATION_PRIVATE_KEY_FILE'] ?? $_ENV['NENE_SUITE_FEDERATION_PRIVATE_KEY_FILE'] ?? ''));

    if ($file !== '' && is_readable($file)) {
        $privateKeyPem = (string) file_get_contents($file);
    }
}

try {
    $preflight = $container->get(FederationKeyPreflight::class);
    assert($preflight instanceof FederationKeyPreflight);
    $preflight->verify($privateKeyPem);
} catch (FederationKeyPreflightException $exception) {
    fwrite(STDERR, '[preflight] FATAL: federation signing key is misconfigured: ' . $exception->getMessage() . "\n");
    exit(1);
} catch (Throwable $exception) {
    fwrite(STDERR, '[preflight] FATAL: federation key preflight error: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "[preflight] federation signing key OK\n";
exit(0);
