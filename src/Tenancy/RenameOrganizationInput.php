<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class RenameOrganizationInput
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public ?string $requestId = null,
    ) {
    }
}
