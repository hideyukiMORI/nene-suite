<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface ChangeMembershipRoleUseCaseInterface
{
    public function execute(ChangeMembershipRoleInput $input): ChangeMembershipRoleOutput;
}
