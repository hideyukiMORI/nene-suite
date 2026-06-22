<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * RFC 7638 JWK thumbprint — the canonical, deterministic key id (`kid`) for a JWK. Siblings pin
 * and cache keys by this value (ADR 0012 §3). For an EC key the thumbprint is the base64url
 * SHA-256 of the JSON object containing exactly the required members `crv`, `kty`, `x`, `y` in
 * lexicographic order with no whitespace.
 */
final class JwkThumbprint
{
    public static function computeEc(string $crv, string $x, string $y): string
    {
        // Required members only, lexicographic order (crv < kty < x < y), no whitespace (RFC 7638 §3.2).
        $canonical = (string) json_encode(['crv' => $crv, 'kty' => 'EC', 'x' => $x, 'y' => $y]);

        return rtrim(strtr(base64_encode(hash('sha256', $canonical, true)), '+/', '-_'), '=');
    }
}
