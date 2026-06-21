<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use NeNeSuite\Tenancy\Organization;

final readonly class BackfillTenancyOutput
{
    public function __construct(
        public ?Organization $organization,
        public int $operatorsBackfilled,
        public bool $skipped,
    ) {
    }
}
