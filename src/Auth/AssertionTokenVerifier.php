<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use Throwable;

/**
 * Verifies **ES256** federation assertions against the published public keys (ADR 0012 JWKS),
 * selected by the token's `kid`. Hardened against the usual IdP-verifier failure modes:
 *
 * - **Algorithm pinning.** Each {@see Key} fixes the algorithm to ES256, so a token presenting
 *   `alg: none` or the classic HS256-with-the-public-key alg-confusion attack is rejected.
 * - **kid required + known.** Passing a keyed map makes the library require the token's `kid` to
 *   match a configured key; an absent or unknown `kid` fails.
 * - Signature, `exp`, and `nbf` are enforced by the library.
 *
 * Any failure surfaces as a {@see TokenVerificationException}. This verifier is never reused on
 * the apex HS256 path (no alg-confusion cross-wiring). Dark in B1.4 — see {@see AssertionTokenIssuer}.
 */
final readonly class AssertionTokenVerifier implements TokenVerifierInterface
{
    /**
     * @param array<string, string> $publicKeysByKid `kid` => PEM-encoded EC public key
     */
    public function __construct(
        private array $publicKeysByKid,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $token): array
    {
        if ($this->publicKeysByKid === []) {
            throw new TokenVerificationException('No federation verification keys are configured.');
        }

        try {
            $keys = [];
            foreach ($this->publicKeysByKid as $kid => $pem) {
                $keys[$kid] = new Key($pem, 'ES256');
            }

            $decoded = JWT::decode($token, $keys);

            /** @var array<string, mixed> $claims */
            $claims = json_decode((string) json_encode($decoded), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new TokenVerificationException('Federation assertion verification failed.', 0, $exception);
        }

        return $claims;
    }
}
