<?php

declare(strict_types=1);

/**
 * NeNe Suite — generate the federation IdP signing key (milestone B1.5).
 *
 * Hosted edition only. Run once to bootstrap the single active signing key; replacing it later is
 * a deliberate rotation (a separate command, milestone B1.8), not a re-run of this script.
 *
 * Usage (inside Docker Compose):
 *   docker compose run --rm suite php ops/keys/generate-federation-key.php
 *
 * Prints the `kid` + public JWK (which ARE stored, public-only) and the PRIVATE KEY (which is NOT
 * stored) for the operator to place out of band as NENE_SUITE_FEDERATION_PRIVATE_KEY.
 */

use NeNeSuite\Auth\ActiveFederationSigningKeyExistsException;
use NeNeSuite\Auth\GenerateFederationSigningKeyInput;
use NeNeSuite\Auth\GenerateFederationSigningKeyUseCaseInterface;
use NeNeSuite\Http\Edition;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Http\RuntimeServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

$edition = $container->get(RuntimeServiceProvider::EDITION);

if (!$edition instanceof Edition || !$edition->isHosted()) {
    fwrite(STDERR, "Refusing: federation signing keys are for the hosted edition only (set NENE_SUITE_EDITION=hosted).\n");
    exit(1);
}

$useCase = $container->get(GenerateFederationSigningKeyUseCaseInterface::class);
assert($useCase instanceof GenerateFederationSigningKeyUseCaseInterface);

try {
    $output = $useCase->execute(new GenerateFederationSigningKeyInput());
} catch (ActiveFederationSigningKeyExistsException $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: key generation failed — " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nFederation signing key generated (status: active).\n";
echo str_repeat('=', 60) . "\n";
echo 'kid        : ' . $output->kid . "\n";
echo 'public JWK : ' . $output->publicJwk . "\n";
echo str_repeat('=', 60) . "\n\n";
echo "PRIVATE KEY — store out of band as NENE_SUITE_FEDERATION_PRIVATE_KEY.\n";
echo "It is NOT stored by the suite and will NOT be shown again:\n\n";
echo $output->privateKeyPem . "\n";

exit(0);
