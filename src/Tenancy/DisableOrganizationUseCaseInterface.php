<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface DisableOrganizationUseCaseInterface
{
    public function execute(DisableOrganizationInput $input): DisableOrganizationOutput;
}
