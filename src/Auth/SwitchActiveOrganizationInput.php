<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class SwitchActiveOrganizationInput
{
    public function __construct(
        public string $operatorId,
        public string $organizationId,
    ) {
    }
}
