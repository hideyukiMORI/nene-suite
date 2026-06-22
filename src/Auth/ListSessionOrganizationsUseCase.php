<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;

/**
 * Lists the organizations the current operator belongs to (milestone §7 ③), oldest first, each
 * with the operator's role. Read-only — no transaction, no audit. Mirrors
 * {@see OperatorSessionContextResolver}: the platform `superadmin` membership (null organization)
 * is skipped, and a membership whose organization no longer exists is degraded out of the list
 * rather than failing.
 */
final readonly class ListSessionOrganizationsUseCase implements ListSessionOrganizationsUseCaseInterface
{
    public function __construct(
        private MembershipRepositoryInterface $memberships,
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(string $operatorId): ListSessionOrganizationsOutput
    {
        $sessionOrganizations = [];

        foreach ($this->memberships->findByOperator($operatorId) as $membership) {
            if ($membership->organizationId === null) {
                continue;
            }

            $organization = $this->organizations->findById($membership->organizationId);

            if ($organization === null) {
                continue;
            }

            $sessionOrganizations[] = new SessionOrganization(
                organizationId: $organization->id,
                externalId: $organization->externalId,
                name: $organization->name,
                slug: $organization->slug,
                role: $membership->role,
            );
        }

        return new ListSessionOrganizationsOutput($sessionOrganizations);
    }
}
