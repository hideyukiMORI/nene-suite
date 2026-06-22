<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * A freshly generated federation key pair. The `privateKeyPem` is returned to the operator to
 * place out of band (`NENE_SUITE_FEDERATION_PRIVATE_KEY`) and is **never** persisted by the suite;
 * only `publicJwk` (a JSON JWK) is stored. `kid` is the RFC 7638 thumbprint.
 */
final readonly class GeneratedFederationKey
{
    public function __construct(
        public string $privateKeyPem,
        public string $publicJwk,
        public string $kid,
        public string $alg,
    ) {
    }
}
