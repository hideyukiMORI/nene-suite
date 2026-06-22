<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\Role;

/**
 * One organization the current operator belongs to, with the operator's role in it (milestone
 * §7 ③). Drives the active-organization switcher. The platform `superadmin` membership (no
 * organization) is not represented here — it is the separate cross-tenant plane.
 */
final readonly class SessionOrganization
{
    public function __construct(
        public string $organizationId,
        public string $externalId,
        public string $name,
        public string $slug,
        public Role $role,
    ) {
    }
}
