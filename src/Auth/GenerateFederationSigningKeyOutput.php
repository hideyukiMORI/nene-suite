<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Result of generating a federation signing key. `privateKeyPem` is returned for the operator to
 * store out of band (`NENE_SUITE_FEDERATION_PRIVATE_KEY`); it is never persisted by the suite.
 */
final readonly class GenerateFederationSigningKeyOutput
{
    public function __construct(
        public string $privateKeyPem,
        public string $kid,
        public string $publicJwk,
    ) {
    }
}
