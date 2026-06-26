<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use InvalidArgumentException;

/**
 * Builds a validated {@see DatabaseTarget} from the raw pieces of a target choice
 * (mode, optional server, optional name), applying the ADR 0021 invariants in one
 * place so every entry point shares them:
 *
 * - `provision` always uses the suite convention name ({@see AppDatabaseNamer}); the
 *   supplied `$name` applies to `adopt` only (where the existing name is what matters),
 *   and falls back to the convention when unset.
 * - The resolved database name must match a conservative identifier charset (defence in
 *   depth — the name flows into env, the manifest, and, for `provision`, DDL).
 * - `provision` on an external server is refused — external is adopt-only in the Tier B
 *   MVP (ADR 0021 OQ2).
 *
 * Both the env path ({@see EnvDatabaseTargetResolver}) and the operator/session path
 * (ADR 0022 mode A) run their inputs through this factory, so a target supplied through
 * the install wizard is validated identically to one supplied through `NENE_SUITE_APP_*`.
 */
final readonly class DatabaseTargetFactory
{
    public function __construct(
        private AppDatabaseNamer $namer,
    ) {
    }

    /**
     * @throws InvalidArgumentException                on an unsafe database name
     * @throws ExternalProvisionNotSupportedException  when `provision` is paired with an external server (ADR 0021 OQ2)
     */
    public function create(string $catalogId, DatabaseTargetMode $mode, ?string $server, ?string $name): DatabaseTarget
    {
        $databaseName = match ($mode) {
            DatabaseTargetMode::Provision => $this->namer->databaseName($catalogId),
            DatabaseTargetMode::Adopt => $name ?? $this->namer->databaseName($catalogId),
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

    /**
     * Conservative database-identifier charset. Provision names come from
     * {@see AppDatabaseNamer} and always pass; adopt names are operator-supplied
     * existing database names and must stay within this safe set.
     */
    private function isSafeDatabaseName(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) === 1;
    }
}
