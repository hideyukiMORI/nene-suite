<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use Exception;
use SodiumException;

/**
 * A public verification key from root.json (a JWK; public members only — private
 * members never appear, root.schema.json forbids them). Ed25519 (kty=OKP,
 * alg=EdDSA) is the preferred/implemented key; EC / RSA are modelled for the
 * allowlist and land with their verifier (O1 follow-up).
 */
final readonly class OriginPublicKey
{
    public function __construct(
        public string $kid,
        public string $kty,
        public ?string $alg,
        public ?string $crv,
        public ?string $x,
        public ?string $y,
        public ?string $n,
        public ?string $e,
        public string $status,
        public ?DateTimeImmutable $notAfter,
    ) {
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws OriginVerificationException when required JWK members are missing/malformed
     */
    public static function fromJwk(array $jwk): self
    {
        $kid = self::requiredString($jwk, 'kid');
        $kty = self::requiredString($jwk, 'kty');
        $status = self::requiredString($jwk, 'status');

        $rawNotAfter = self::optionalString($jwk, 'not_after');
        $notAfter = null;

        if ($rawNotAfter !== null) {
            try {
                $notAfter = new DateTimeImmutable($rawNotAfter);
            } catch (Exception) {
                throw OriginVerificationException::malformedKey(sprintf('invalid not_after: %s', $rawNotAfter));
            }
        }

        return new self(
            kid: $kid,
            kty: $kty,
            alg: self::optionalString($jwk, 'alg'),
            crv: self::optionalString($jwk, 'crv'),
            x: self::optionalString($jwk, 'x'),
            y: self::optionalString($jwk, 'y'),
            n: self::optionalString($jwk, 'n'),
            e: self::optionalString($jwk, 'e'),
            status: $status,
            notAfter: $notAfter,
        );
    }

    /** kid-valid-at-iat: a signature with this key is acceptable only if iat ≤ not_after. */
    public function isValidAt(DateTimeImmutable $iat): bool
    {
        return $this->notAfter === null || $iat <= $this->notAfter;
    }

    /**
     * Raw 32-byte Ed25519 public key for libsodium. Only valid for an OKP/Ed25519 key.
     *
     * @throws OriginVerificationException when this is not a well-formed Ed25519 key
     */
    public function ed25519PublicKey(): string
    {
        if ($this->kty !== 'OKP' || $this->crv !== 'Ed25519' || $this->x === null) {
            throw OriginVerificationException::malformedKey('not an Ed25519 (OKP) public key');
        }

        try {
            $raw = Base64Url::decode($this->x);
        } catch (SodiumException) {
            throw OriginVerificationException::malformedKey('invalid base64url in key x');
        }

        if (strlen($raw) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw OriginVerificationException::malformedKey('Ed25519 public key must be 32 bytes');
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $jwk
     */
    private static function requiredString(array $jwk, string $key): string
    {
        $value = $jwk[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw OriginVerificationException::malformedKey(sprintf('missing or invalid JWK member: %s', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $jwk
     */
    private static function optionalString(array $jwk, string $key): ?string
    {
        $value = $jwk[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
