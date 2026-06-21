<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

interface BootstrapDefaultOrganizationUseCaseInterface
{
    public function execute(BootstrapDefaultOrganizationInput $input): BootstrapDefaultOrganizationOutput;
}
