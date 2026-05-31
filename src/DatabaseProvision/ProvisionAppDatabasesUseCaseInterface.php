<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

interface ProvisionAppDatabasesUseCaseInterface
{
    public function execute(ProvisionAppDatabasesInput $input): ProvisionAppDatabasesOutput;
}
