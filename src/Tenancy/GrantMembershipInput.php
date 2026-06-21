<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class GrantMembershipInput
{
    public function __construct(
        public string $operatorId,
        public Role $role,
        public ?string $organizationId = null,
        public ?string $requestId = null,
    ) {
    }
}
