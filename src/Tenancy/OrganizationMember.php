<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * An organization membership enriched with the operator's identity (milestone A8b) for the
 * superadmin member list. `email`/`displayName` are null when the membership points at an
 * operator that no longer exists (a stale membership).
 */
final readonly class OrganizationMember
{
    public function __construct(
        public string $membershipId,
        public string $operatorId,
        public ?string $email,
        public ?string $displayName,
        public Role $role,
    ) {
    }
}
