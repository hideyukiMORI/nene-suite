<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class CreateOrganizationInput
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $requestId = null,
    ) {
    }
}
