<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class RenameOrganizationOutput
{
    public function __construct(
        public Organization $organization,
    ) {
    }
}
