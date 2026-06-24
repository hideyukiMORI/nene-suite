<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Thin libsodium (Ed25519) verify wrapper — the only signature primitive the profiled-TUF read
 * contract needs in practice (`EdDSA` is preferred, verification-order.md §2). A consumer mirrors
 * this with libsodium / openssl directly; no generic JWS/TUF parser is involved.
 */
final class OriginEd25519
{
    public static function verify(string $signature, string $message, string $publicKey): bool
    {
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
    }
}
