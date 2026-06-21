<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface GrantMembershipUseCaseInterface
{
    public function execute(GrantMembershipInput $input): GrantMembershipOutput;
}
