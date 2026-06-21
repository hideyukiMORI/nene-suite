<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class DisableOrganizationOutput
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
