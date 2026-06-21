<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use NeNeSuite\Auth\AuthenticatedPrincipal;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Default-deny gate for the platform (cross-tenant) management surface (milestone A7): the
 * single place the superadmin rule is enforced. Authenticates first (missing/invalid token →
 * 401 via the authenticator), then requires the platform `superadmin` claim (403 otherwise).
 * The gate reads `isSuperadmin` only — never `role`, which is an org role.
 */
final readonly class SuperadminGuard
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
    ) {
    }

    /**
     * @throws \NeNeSuite\Auth\UnauthorizedException when the token is missing or invalid (401)
     * @throws ForbiddenException when the operator is not a platform superadmin (403)
     */
    public function ensure(ServerRequestInterface $request): AuthenticatedPrincipal
    {
        $principal = $this->authenticator->principal($request);

        if (!$principal->isSuperadmin) {
            throw new ForbiddenException();
        }

        return $principal;
    }
}
