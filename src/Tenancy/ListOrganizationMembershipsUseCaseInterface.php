<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface ListOrganizationMembershipsUseCaseInterface
{
    public function execute(string $organizationId): ListOrganizationMembershipsOutput;
}
