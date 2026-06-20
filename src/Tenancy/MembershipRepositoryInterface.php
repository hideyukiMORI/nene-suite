<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface MembershipRepositoryInterface
{
    public function save(Membership $membership): void;

    public function findById(string $id): ?Membership;

    /**
     * All memberships for an operator, oldest first.
     *
     * @return list<Membership>
     */
    public function findByOperator(string $operatorId): array;

    /**
     * The operator's membership in a specific organization, or the platform
     * membership when `$organizationId` is null.
     */
    public function findByOperatorAndOrganization(string $operatorId, ?string $organizationId): ?Membership;

    /**
     * All memberships in an organization, oldest first.
     *
     * @return list<Membership>
     */
    public function findByOrganization(string $organizationId): array;
}
