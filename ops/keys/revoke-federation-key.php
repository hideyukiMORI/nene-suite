<?php

declare(strict_types=1);

/**
 * NeNe Suite — emergency-revoke a federation IdP signing key by kid (milestone B1.8). Hosted only.
 *
 * Marks the key `revoked`: it is dropped from the JWKS immediately and the verifier rejects any
 * assertion bearing that kid. Use on key compromise. NOTE: siblings cache the JWKS and refresh on an
 * unknown kid, so a revoked-but-still-cached kid may keep verifying at a sibling until its JWKS cache
 * expires — the real recovery window is the JWKS cache max-age (see docs/ops/federation-key-management.md).
 *
 * Usage: docker compose run --rm suite php ops/keys/revoke-federation-key.php <kid>
 */

use NeNeSuite\Auth\FederationSigningKeyNotFoundException;
use NeNeSuite\Auth\RevokeFederationSigningKeyInput;
use NeNeSuite\Auth\RevokeFederationSigningKeyUseCaseInterface;
use NeNeSuite\Http\Edition;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Http\RuntimeServiceProvider;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$kid = isset($argv[1]) ? trim((string) $argv[1]) : '';

if ($kid === '') {
    fwrite(STDERR, "Usage: php ops/keys/revoke-federation-key.php <kid>\n");
    exit(1);
}

$container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

$edition = $container->get(RuntimeServiceProvider::EDITION);

if (!$edition instanceof Edition || !$edition->isHosted()) {
    fwrite(STDERR, "Refusing: federation key revocation is for the hosted edition only (set NENE_SUITE_EDITION=hosted).\n");
    exit(1);
}

$useCase = $container->get(RevokeFederationSigningKeyUseCaseInterface::class);
assert($useCase instanceof RevokeFederationSigningKeyUseCaseInterface);

try {
    $revokedNow = $useCase->execute(new RevokeFederationSigningKeyInput($kid));
} catch (FederationSigningKeyNotFoundException $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: key revocation failed — " . $e->getMessage() . "\n");
    exit(1);
}

if ($revokedNow) {
    echo "Revoked federation signing key {$kid} — dropped from the JWKS.\n";
    echo "If it was the active key, generate a new one and update NENE_SUITE_FEDERATION_PRIVATE_KEY.\n";
} else {
    echo "Federation signing key {$kid} was already revoked; no change.\n";
}

exit(0);
