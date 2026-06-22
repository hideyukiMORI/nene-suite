<?php

declare(strict_types=1);

/**
 * NeNe Suite — rotate the federation IdP signing key (milestone B1.8). Hosted edition only.
 *
 * Demotes the current active key to `retiring` (kept in the JWKS during the grace window so
 * in-flight assertions still verify), mints a NEW active key, and retires any previously-retiring
 * key. After running: set the printed PRIVATE KEY as NENE_SUITE_FEDERATION_PRIVATE_KEY and restart
 * the apex so it signs with the new kid. Keep the old key published for ≥ the assertion TTL.
 *
 * Usage: docker compose run --rm suite php ops/keys/rotate-federation-key.php
 */

use NeNeSuite\Auth\GenerateFederationSigningKeyInput;
use NeNeSuite\Auth\NoActiveFederationSigningKeyException;
use NeNeSuite\Auth\RotateFederationSigningKeyUseCaseInterface;
use NeNeSuite\Http\Edition;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Http\RuntimeServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

$edition = $container->get(RuntimeServiceProvider::EDITION);

if (!$edition instanceof Edition || !$edition->isHosted()) {
    fwrite(STDERR, "Refusing: federation key rotation is for the hosted edition only (set NENE_SUITE_EDITION=hosted).\n");
    exit(1);
}

$useCase = $container->get(RotateFederationSigningKeyUseCaseInterface::class);
assert($useCase instanceof RotateFederationSigningKeyUseCaseInterface);

try {
    $output = $useCase->execute(new GenerateFederationSigningKeyInput());
} catch (NoActiveFederationSigningKeyException $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: key rotation failed — " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nFederation signing key rotated. New key is active; the previous key is retiring (still in JWKS).\n";
echo str_repeat('=', 60) . "\n";
echo 'new kid    : ' . $output->kid . "\n";
echo 'public JWK : ' . $output->publicJwk . "\n";
echo str_repeat('=', 60) . "\n\n";
echo "NEXT STEPS:\n";
echo "  1. Set the new PRIVATE KEY below as NENE_SUITE_FEDERATION_PRIVATE_KEY and restart the apex.\n";
echo "  2. Keep the retiring key published until in-flight assertions expire (assertion TTL).\n";
echo "It is NOT stored by the suite and will NOT be shown again:\n\n";
echo $output->privateKeyPem . "\n";

exit(0);
