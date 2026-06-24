<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use SodiumException;

/**
 * Verifies one detached-JWS sidecar against a public key, per verification-order.md:
 * a per-major algorithm allowlist (asymmetric only, never "none"), and the RFC 7797
 * signing input `ASCII(BASE64URL(protected) + ".") ‖ <raw body bytes>` checked with
 * libsodium / openssl — never a generic JWS parser, never a re-serialized body.
 *
 * EdDSA (Ed25519, libsodium) is implemented now (Origin's preferred/default key).
 * ES256/ES384/RS256 are allowlisted but their consumer verifier lands in a follow-up.
 * `kid`-valid-at-`iat` is enforced by the chain orchestrator (O1b) — the signature
 * `iat` source is a separate Origin spec point; {@see OriginPublicKey::isValidAt()}.
 */
final readonly class OriginSignatureVerifier
{
    private const ALLOWED_ALGS = ['EdDSA', 'ES256', 'ES384', 'RS256'];

    private const IMPLEMENTED_ALGS = ['EdDSA'];

    /**
     * @throws OriginVerificationException when the algorithm is disallowed/unimplemented,
     *                                     the key mismatches, or the signature is invalid
     */
    public function verify(DetachedJws $jws, string $body, OriginPublicKey $key): void
    {
        if (!in_array($jws->alg, self::ALLOWED_ALGS, true)) {
            throw OriginVerificationException::algorithmDisallowed($jws->alg);
        }

        if ($key->alg !== null && $key->alg !== $jws->alg) {
            throw OriginVerificationException::keyAlgorithmMismatch(
                sprintf('key alg %s != signature alg %s', $key->alg, $jws->alg),
            );
        }

        if (!in_array($jws->alg, self::IMPLEMENTED_ALGS, true)) {
            throw OriginVerificationException::algorithmNotImplemented($jws->alg);
        }

        $this->verifyEdDsa($jws, $body, $key);
    }

    private function verifyEdDsa(DetachedJws $jws, string $body, OriginPublicKey $key): void
    {
        $publicKey = $key->ed25519PublicKey();
        $signingInput = $jws->protectedB64 . '.' . $body;

        if ($jws->signature === '' || $publicKey === '') {
            throw OriginVerificationException::signatureInvalid('empty signature or key');
        }

        try {
            $verified = sodium_crypto_sign_verify_detached($jws->signature, $signingInput, $publicKey);
        } catch (SodiumException) {
            throw OriginVerificationException::signatureInvalid('malformed Ed25519 signature');
        }

        if (!$verified) {
            throw OriginVerificationException::signatureInvalid();
        }
    }
}
