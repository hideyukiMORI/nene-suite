<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\Role;

/**
 * The authenticated apex operator plus their active-org context (milestone A6), returned by
 * {@see BearerTokenAuthenticator::principal()}. Backend authorization (A7) reads
 * `isSuperadmin` for the platform plane and `role` for organization-scoped access.
 */
final readonly class AuthenticatedPrincipal
{
    public function __construct(
        public string $operatorId,
        public ?string $orgExternalId,
        public ?Role $role,
        public bool $isSuperadmin,
    ) {
    }
}
