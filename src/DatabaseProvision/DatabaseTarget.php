<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * The per-app database target (ADR 0021 §1): which database an app uses, on which
 * server, obtained how. The default target (`Provision`, suite server, catalog-id name)
 * reproduces the suite's historical single-model behavior.
 *
 * `$server` is a **non-secret** host / label only — null means the suite's own
 * provisioning server (`NENE_SUITE_PROVISION_DB_*`). Connection credentials are never
 * carried here; they live in the app's own `*_DB_*` env (catalog `database.env_prefix`)
 * and are never persisted to the manifest / audit (ADR 0011 secrets policy).
 */
final readonly class DatabaseTarget
{
    public function __construct(
        public string $catalogId,
        public DatabaseTargetMode $mode,
        public string $databaseName,
        public ?string $server = null,
    ) {
    }

    /**
     * True when the database lives on a server other than the suite's own provisioning
     * server. External servers are adopt-only in the Tier B MVP (ADR 0021 OQ2).
     */
    public function isExternal(): bool
    {
        return $this->server !== null;
    }
}
