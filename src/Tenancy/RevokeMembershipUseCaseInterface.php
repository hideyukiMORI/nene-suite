<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface RevokeMembershipUseCaseInterface
{
    public function execute(RevokeMembershipInput $input): RevokeMembershipOutput;
}
