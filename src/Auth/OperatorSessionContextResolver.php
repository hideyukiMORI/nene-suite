<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use NeNeSuite\Tenancy\Role;

/**
 * Resolves an operator's active-org session context (milestone A6) from their memberships:
 * platform superadmin status (any `Superadmin` membership) and the active organization (the
 * oldest org-scoped membership — an interactive switcher is deferred to A8). A membership
 * pointing at a missing organization degrades to "no active org" rather than failing. An
 * operator with no memberships resolves to `{null, null, false}` (login still succeeds; role
 * gating yields 403 later, never a 500 here).
 */
final readonly class OperatorSessionContextResolver
{
    public function __construct(
        private MembershipRepositoryInterface $memberships,
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function resolve(string $operatorId): OperatorSessionContext
    {
        $isSuperadmin = false;
        $active = null;

        // findByOperator returns memberships oldest-first, so the first org-scoped one is the
        // oldest — the deterministic active org until A8 adds an interactive switch.
        foreach ($this->memberships->findByOperator($operatorId) as $membership) {
            if ($membership->role === Role::Superadmin) {
                $isSuperadmin = true;

                continue;
            }

            if ($active === null && $membership->organizationId !== null) {
                $active = $membership;
            }
        }

        if ($active === null || $active->organizationId === null) {
            return new OperatorSessionContext(null, null, $isSuperadmin);
        }

        $organization = $this->organizations->findById($active->organizationId);

        if ($organization === null) {
            return new OperatorSessionContext(null, null, $isSuperadmin);
        }

        return new OperatorSessionContext($organization->externalId, $active->role, $isSuperadmin);
    }
}
