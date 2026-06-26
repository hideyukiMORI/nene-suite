<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use InvalidArgumentException;

/**
 * Resolves the per-app database target from suite environment variables (ADR 0021 OQ1),
 * parallel to `NENE_SUITE_APP_{SNAKE}_URL` (terminology §4.1):
 *
 * - `NENE_SUITE_APP_{SNAKE}_DB_MODE`   — `provision` (default) | `adopt`
 * - `NENE_SUITE_APP_{SNAKE}_DB_SERVER` — non-secret host / label; unset = suite server
 * - `NENE_SUITE_APP_{SNAKE}_DB_NAME`   — adopt only: the existing database name; unset falls back
 *                                        to the suite convention (`AppDatabaseNamer`)
 *
 * `{SNAKE}` = the catalog id with hyphens replaced by underscores, upper-cased. Reads
 * `$_SERVER` / `$_ENV`, the same source NENE2's ConfigLoader populates from `.env`.
 *
 * Defaults reproduce the historical single model exactly: `provision`, suite server,
 * catalog-id name. In `provision` mode the database name always follows the suite
 * convention (the `_DB_NAME` override applies to `adopt`, where the existing name is
 * what matters).
 */
final readonly class EnvDatabaseTargetResolver implements DatabaseTargetResolverInterface
{
    public function __construct(
        private AppDatabaseNamer $namer,
    ) {
    }

    public function resolve(string $catalogId): DatabaseTarget
    {
        $snake = strtoupper(str_replace('-', '_', $catalogId));

        $mode = DatabaseTargetMode::fromString(
            $this->env("NENE_SUITE_APP_{$snake}_DB_MODE") ?? DatabaseTargetMode::Provision->value,
        );

        $server = $this->env("NENE_SUITE_APP_{$snake}_DB_SERVER");

        $databaseName = match ($mode) {
            DatabaseTargetMode::Provision => $this->namer->databaseName($catalogId),
            DatabaseTargetMode::Adopt => $this->env("NENE_SUITE_APP_{$snake}_DB_NAME")
                ?? $this->namer->databaseName($catalogId),
        };

        if (!$this->isSafeDatabaseName($databaseName)) {
            throw new InvalidArgumentException(
                "Invalid database name for '{$catalogId}': {$databaseName}",
            );
        }

        // MVP (ADR 0021 OQ2): provisioning is suite-server only; external servers are adopt-only.
        if ($mode === DatabaseTargetMode::Provision && $server !== null) {
            throw new ExternalProvisionNotSupportedException($catalogId);
        }

        return new DatabaseTarget($catalogId, $mode, $databaseName, $server);
    }

    private function env(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Conservative database-identifier charset (defence in depth — the name flows into
     * env, the manifest, and, for `provision`, DDL). Provision names come from
     * `AppDatabaseNamer` and always pass; adopt names are operator-supplied existing
     * database names and must stay within this safe set.
     */
    private function isSafeDatabaseName(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) === 1;
    }
}
