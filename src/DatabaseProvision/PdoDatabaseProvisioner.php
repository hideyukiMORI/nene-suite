<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use PDO;
use RuntimeException;

/**
 * Creates per-app databases via a privileged PDO connection (MySQL or PostgreSQL — ADR 0016).
 * Idempotent: MySQL uses `CREATE DATABASE IF NOT EXISTS`; PostgreSQL has no such clause and
 * cannot run CREATE DATABASE inside a transaction, so it checks `pg_database` first.
 * The engine is detected from the connection (`PDO::ATTR_DRIVER_NAME`).
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

        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            // PostgreSQL: no IF NOT EXISTS for CREATE DATABASE and it cannot run in a
            // transaction (PDO is in autocommit here). Check existence, then create.
            $stmt = $this->pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
            $stmt->execute([$databaseName]);

            if ($stmt->fetchColumn() === false) {
                $this->pdo->exec(self::postgresCreateSql($databaseName));
            }

            return;
        }

        $this->pdo->exec(self::mysqlCreateSql($databaseName));
    }

    /**
     * MySQL CREATE DATABASE DDL for a pre-validated name.
     *
     * @internal exposed for unit testing the generated DDL
     */
    public static function mysqlCreateSql(string $databaseName): string
    {
        return "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    /**
     * PostgreSQL CREATE DATABASE DDL for a pre-validated name (no IF NOT EXISTS — guard
     * with an existence check before calling).
     *
     * @internal exposed for unit testing the generated DDL
     */
    public static function postgresCreateSql(string $databaseName): string
    {
        return "CREATE DATABASE \"{$databaseName}\" ENCODING 'UTF8' TEMPLATE template0";
    }

    private function isValidDatabaseName(string $name): bool
    {
        return $name !== '' && preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1;
    }
}
