<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use PDO;
use RuntimeException;

/**
 * Creates per-app MySQL databases via a privileged PDO connection.
 * Uses `CREATE DATABASE IF NOT EXISTS` to satisfy the idempotence requirement.
 * Database names are validated against the suite naming convention before execution
 * to prevent SQL injection (names arrive from AppDatabaseNamer, not user input, but
 * defence in depth applies).
 */
final readonly class PdoDatabaseProvisioner implements DatabaseProvisionerInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function provision(string $databaseName): void
    {
        if (!$this->isValidDatabaseName($databaseName)) {
            throw new RuntimeException("Invalid database name: {$databaseName}");
        }

        $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function isValidDatabaseName(string $name): bool
    {
        return $name !== '' && preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1;
    }
}
