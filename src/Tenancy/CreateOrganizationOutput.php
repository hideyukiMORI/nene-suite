<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class CreateOrganizationOutput
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
