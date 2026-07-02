<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class EnableOrganizationOutput
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
