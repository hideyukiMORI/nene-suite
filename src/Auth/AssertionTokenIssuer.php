<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Firebase\JWT\JWT;
use Nene2\Auth\TokenIssuerInterface;

/**
 * Issues asymmetric **ES256** federation assertions (ADR 0012 §3/§4) via the vetted
 * `firebase/php-jwt` library — deliberately never a hand-rolled ECDSA encoder (raw `openssl_sign`
 * emits ASN.1/DER signatures while JOSE requires raw R||S, a classic verification/malleability
 * trap). It carries the signing key's `kid` in the JWT header so verifiers can select the key
 * from the published JWKS and the suite can rotate keys.
 *
 * This is a SEPARATE binding from the apex session signer ({@see \Nene2\Auth\LocalBearerTokenVerifier},
 * HS256): the two key types and trust domains must never be conflated (ADR 0012 §4). Dark in B1.4
 * — not wired to any route or flow (the signing-key store is B1.5, the JWKS endpoint B1.6, and the
 * assertion-issuance flow B2).
 */
final readonly class AssertionTokenIssuer implements TokenIssuerInterface
{
    public function __construct(
        private string $privateKeyPem,
        private string $kid,
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims): string
    {
        return JWT::encode($claims, $this->privateKeyPem, 'ES256', $this->kid);
    }
}
