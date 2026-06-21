<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use NeNeSuite\Tenancy\Organization;

final readonly class BootstrapDefaultOrganizationOutput
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
