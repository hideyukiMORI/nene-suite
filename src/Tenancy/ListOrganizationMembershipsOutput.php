<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class ListOrganizationMembershipsOutput
{
    /**
     * @param list<OrganizationMember> $members
     */
    public function __construct(
        public array $members,
    ) {
    }
}
