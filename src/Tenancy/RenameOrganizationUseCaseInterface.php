<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface RenameOrganizationUseCaseInterface
{
    public function execute(RenameOrganizationInput $input): RenameOrganizationOutput;
}
