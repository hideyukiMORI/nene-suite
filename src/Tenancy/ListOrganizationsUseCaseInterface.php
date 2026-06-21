<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface ListOrganizationsUseCaseInterface
{
    public function execute(): ListOrganizationsOutput;
}
