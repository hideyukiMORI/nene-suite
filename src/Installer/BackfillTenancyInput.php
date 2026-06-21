<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

final readonly class BackfillTenancyInput
{
    public function __construct(
        public ?string $requestId = null,
    ) {
    }
}
