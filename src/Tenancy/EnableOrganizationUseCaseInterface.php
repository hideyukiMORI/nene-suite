<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface EnableOrganizationUseCaseInterface
{
    public function execute(EnableOrganizationInput $input): EnableOrganizationOutput;
}
