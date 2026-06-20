<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * Links an operator to an organization with a role. A platform `Superadmin`
 * membership has no organization (`organizationId` is null); all other roles are
 * scoped to one organization.
 */
final readonly class Membership
{
    public function __construct(
        public string $id,
        public string $operatorId,
        public ?string $organizationId,
        public Role $role,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
