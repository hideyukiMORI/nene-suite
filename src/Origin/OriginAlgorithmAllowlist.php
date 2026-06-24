<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The per-major signature-algorithm allowlist (verification-order.md §2): asymmetric only, `EdDSA`
 * preferred, `ES256` / `ES384` / `RS256` permitted, and crucially **`"none"` forbidden** — the
 * classic JWS downgrade. Symmetric (`HS*`) and unknown values are rejected. Checked **before**
 * trusting a signature.
 */
final class OriginAlgorithmAllowlist
{
    /** @var list<string> */
    public const array ALLOWED = ['EdDSA', 'ES256', 'ES384', 'RS256'];

    public static function isAllowed(mixed $alg): bool
    {
        return is_string($alg) && in_array($alg, self::ALLOWED, true);
    }
}
