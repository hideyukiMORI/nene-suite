<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\Role;

/**
 * An operator's resolved active-org session context (milestone A6): the organization the
 * session is scoped to, the operator's role within it, and whether the operator is a platform
 * superadmin. `role` is an organization role (`admin`/`member`/`viewer`) or null — never
 * `Superadmin`, which is the separate `isSuperadmin` platform dimension.
 */
final readonly class OperatorSessionContext
{
    public function __construct(
        public ?string $orgExternalId,
        public ?Role $role,
        public bool $isSuperadmin,
    ) {
    }
}
