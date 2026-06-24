<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use Exception;
use SodiumException;

/**
 * A resolved public verification key — the raw Ed25519 public bytes plus the `not_after` validity
 * bound used for the kid-valid-at-iat rule (a signature is honoured even after the key retires, as
 * long as it was signed while the key was valid; Origin ADR 0006 §4). Built from a root.json JWK.
 *
 * Resolves OKP/Ed25519 keys (the corpus is EdDSA, the preferred algorithm). ES256/ES384/RS256 are
 * allowlisted in the contract; a consumer that must verify those adds an openssl path — the corpus
 * does not exercise them and {@see tryFromJwk} skips them (this mirrors the reference verifier).
 */
final readonly class OriginPublicKeyMaterial
{
    public function __construct(
        public string $kid,
        public string $publicKey,
        public ?DateTimeImmutable $notAfter,
    ) {
    }

    /**
     * Resolve an OKP/Ed25519 JWK to key material, or null for any other key type / malformed entry.
     *
     * @param array<string, mixed> $jwk
     */
    public static function tryFromJwk(array $jwk): ?self
    {
        $kid = $jwk['kid'] ?? null;
        $kty = $jwk['kty'] ?? null;
        $crv = $jwk['crv'] ?? null;
        $x = $jwk['x'] ?? null;

        if (!is_string($kid) || $kty !== 'OKP' || $crv !== 'Ed25519' || !is_string($x)) {
            return null;
        }

        $notAfter = null;
        $rawNotAfter = $jwk['not_after'] ?? null;

        if (is_string($rawNotAfter)) {
            try {
                $notAfter = new DateTimeImmutable($rawNotAfter);
            } catch (Exception) {
                return null; // an unparseable validity bound makes the key unusable (fail closed)
            }
        }

        try {
            $publicKey = Base64Url::decode($x);
        } catch (SodiumException) {
            return null;
        }

        return new self($kid, $publicKey, $notAfter);
    }

    /** True if a signature with this `iat` (epoch seconds) was made while the key was still valid. */
    public function validAt(int $iat): bool
    {
        return $this->notAfter === null || $iat <= $this->notAfter->getTimestamp();
    }
}
