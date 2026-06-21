<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface MembershipRepositoryInterface
{
    public function save(Membership $membership): void;

    /**
     * Persists a membership's role change. `operator_id`, `organization_id`, and
     * `created_at` are immutable — only `role` and `updated_at` are written.
     */
    public function updateRole(Membership $membership): void;

    /**
     * Removes a membership (revokes access). The append-only audit row remains the
     * permanent record (audit-trail §7).
     */
    public function delete(string $id): void;

    public function findById(string $id): ?Membership;

    /**
     * All platform memberships (`organization_id IS NULL`) — i.e. every cross-tenant
     * `superadmin`. Used to enforce the "at least one platform superadmin" invariant.
     *
     * @return list<Membership>
     */
    public function findPlatformMemberships(): array;

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
