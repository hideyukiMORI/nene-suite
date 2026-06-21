<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class ChangeMembershipRoleInput
{
    public function __construct(
        public string $membershipId,
        public Role $role,
        public ?string $requestId = null,
    ) {
    }
}
