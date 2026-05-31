<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * Creates an app-specific database on the target database server.
 * Implementations MUST be idempotent (safe to call when the database already exists).
 */
interface DatabaseProvisionerInterface
{
    public function provision(string $databaseName): void;
}
