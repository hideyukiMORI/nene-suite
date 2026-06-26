<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

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
 * what matters). Validation and the default-name rule live in {@see DatabaseTargetFactory},
 * shared with the operator/session path (ADR 0022 mode A).
 */
final readonly class EnvDatabaseTargetResolver implements DatabaseTargetResolverInterface
{
    public function __construct(
        private DatabaseTargetFactory $factory,
    ) {
    }

    public function resolve(string $catalogId): DatabaseTarget
    {
        $snake = strtoupper(str_replace('-', '_', $catalogId));

        $mode = DatabaseTargetMode::fromString(
            $this->env("NENE_SUITE_APP_{$snake}_DB_MODE") ?? DatabaseTargetMode::Provision->value,
        );

        return $this->factory->create(
            $catalogId,
            $mode,
            $this->env("NENE_SUITE_APP_{$snake}_DB_SERVER"),
            $this->env("NENE_SUITE_APP_{$snake}_DB_NAME"),
        );
    }

    private function env(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
