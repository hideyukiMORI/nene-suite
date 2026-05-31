<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

final readonly class ProvisionAppDatabasesOutput
{
    /**
     * @param list<ProvisionedAppDatabase> $provisioned
     */
    public function __construct(
        public array $provisioned,
    ) {
    }
}
