<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use NeNeSuite\Auth\OperatorRepositoryInterface;

/**
 * Lists an organization's memberships enriched with operator identity (milestone A8b).
 * Read-only — no transaction, no audit. A membership whose operator was removed degrades to
 * null email/displayName rather than being dropped.
 */
final readonly class ListOrganizationMembershipsUseCase implements ListOrganizationMembershipsUseCaseInterface
{
    public function __construct(
        private MembershipRepositoryInterface $memberships,
        private OperatorRepositoryInterface $operators,
    ) {
    }

    public function execute(string $organizationId): ListOrganizationMembershipsOutput
    {
        $members = [];

        foreach ($this->memberships->findByOrganization($organizationId) as $membership) {
            $operator = $this->operators->findById($membership->operatorId);

            $members[] = new OrganizationMember(
                membershipId: $membership->id,
                operatorId: $membership->operatorId,
                email: $operator?->email,
                displayName: $operator?->displayName,
                role: $membership->role,
            );
        }

        return new ListOrganizationMembershipsOutput($members);
    }
}
