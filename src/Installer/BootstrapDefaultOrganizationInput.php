<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

final readonly class BootstrapDefaultOrganizationInput
{
    public function __construct(
        public string $installSessionId,
        public string $orgName,
        public string $operatorId,
        public ?string $requestId = null,
    ) {
    }
}
