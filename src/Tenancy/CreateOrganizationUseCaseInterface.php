<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface CreateOrganizationUseCaseInterface
{
    public function execute(CreateOrganizationInput $input): CreateOrganizationOutput;
}
