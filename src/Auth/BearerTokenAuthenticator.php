<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use NeNeSuite\Tenancy\Role;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts and verifies the apex operator bearer token from a request. Used by authenticated
 * handlers in place of a global middleware while only a few endpoints require auth.
 *
 * `operatorId()` returns just the `sub` claim (the original, stable surface). `principal()`
 * additionally resolves the active-org context (milestone A6) — read from the token's
 * org_external_id/role/superadmin claims, or, for a pre-A6 token that lacks them, resolved
 * server-side from `sub` so existing 24h tokens are not locked out during the rollover.
 */
final readonly class BearerTokenAuthenticator
{
    public function __construct(
        private TokenVerifierInterface $tokenVerifier,
        private OperatorSessionContextResolver $sessionContext,
        private RevokedTokenRepositoryInterface $revokedTokens,
    ) {
    }

    /**
     * The verified claims of the presented token (jti, exp, …). Used by logout to denylist the
     * token. Throws like the other accessors on a missing/invalid/revoked token.
     *
     * @return array<string, mixed>
     *
     * @throws UnauthorizedException
     */
    public function claims(ServerRequestInterface $request): array
    {
        return $this->verifiedClaims($request);
    }

    /**
     * @throws UnauthorizedException when the token is missing, malformed, or invalid
     */
    public function operatorId(ServerRequestInterface $request): string
    {
        return $this->subject($this->verifiedClaims($request));
    }

    /**
     * @throws UnauthorizedException when the token is missing, malformed, or invalid
     */
    public function principal(ServerRequestInterface $request): AuthenticatedPrincipal
    {
        $claims = $this->verifiedClaims($request);
        $operatorId = $this->subject($claims);

        // An A6 token carries the context claims (use array_key_exists, not isset — `superadmin`
        // false and `org_external_id` null are valid values). A pre-A6 token lacks them.
        if (array_key_exists('superadmin', $claims) && array_key_exists('org_external_id', $claims)) {
            $orgExternalId = is_string($claims['org_external_id']) ? $claims['org_external_id'] : null;

            $roleRaw = $claims['role'] ?? null;
            $role = is_string($roleRaw) ? Role::tryFrom($roleRaw) : null;

            if ($role === Role::Superadmin) {
                // `role` is an org role only; the platform dimension is the separate claim.
                $role = null;
            }

            return new AuthenticatedPrincipal($operatorId, $orgExternalId, $role, $claims['superadmin'] === true);
        }

        $context = $this->sessionContext->resolve($operatorId);

        return new AuthenticatedPrincipal($operatorId, $context->orgExternalId, $context->role, $context->isSuperadmin);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws UnauthorizedException
     */
    private function verifiedClaims(ServerRequestInterface $request): array
    {
        $header = $request->getHeaderLine('Authorization');

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            throw new UnauthorizedException();
        }

        try {
            $claims = $this->tokenVerifier->verify(trim($matches[1]));
        } catch (TokenVerificationException) {
            throw new UnauthorizedException();
        }

        // B1.3 logout-revocation: a token carrying a denylisted `jti` is rejected. Pre-B1.3 tokens
        // have no `jti` and are unaffected (rollover-safe, mirroring the A6 claim fallback).
        $jti = $claims['jti'] ?? null;
        if (is_string($jti) && $jti !== '' && $this->revokedTokens->isRevoked($jti)) {
            throw new UnauthorizedException();
        }

        return $claims;
    }

    /**
     * @param array<string, mixed> $claims
     *
     * @throws UnauthorizedException
     */
    private function subject(array $claims): string
    {
        $subject = $claims['sub'] ?? null;

        if (!is_string($subject) || $subject === '') {
            throw new UnauthorizedException();
        }

        return $subject;
    }
}
