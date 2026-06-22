<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * A federation signing key as held by the suite (milestone B1.5). The suite stores only the
 * **public** half (`publicJwk`, a JSON JWK with `kid`/`kty`/`crv`/`x`/`y`/`alg`/`use`); the private
 * key never enters the control DB — it is supplied to the serving runtime out of band
 * (`NENE_SUITE_FEDERATION_PRIVATE_KEY`, hosted edition). `kid` is the RFC 7638 JWK thumbprint.
 */
final readonly class FederationSigningKey
{
    public function __construct(
        public string $id,
        public string $kid,
        public string $alg,
        public string $publicJwk,
        public FederationSigningKeyStatus $status,
        public string $createdAt,
        public ?string $activatedAt,
        public ?string $retiredAt,
    ) {
    }
}
