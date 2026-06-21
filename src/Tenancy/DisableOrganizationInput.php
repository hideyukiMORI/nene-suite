<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class DisableOrganizationInput
{
    public function __construct(
        public string $organizationId,
        public ?string $requestId = null,
    ) {
    }
}
