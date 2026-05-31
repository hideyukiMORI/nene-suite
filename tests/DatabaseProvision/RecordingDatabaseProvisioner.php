<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\DatabaseProvisionerInterface;

/**
 * Test double that records database names passed to provision() for assertions.
 */
final class RecordingDatabaseProvisioner implements DatabaseProvisionerInterface
{
    /** @var list<string> */
    public array $provisioned = [];

    public function provision(string $databaseName): void
    {
        $this->provisioned[] = $databaseName;
    }
}
