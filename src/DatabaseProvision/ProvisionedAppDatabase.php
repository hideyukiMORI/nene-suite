<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * Records the outcome of resolving + provisioning/adopting one app's database (ADR 0021).
 * Safe to include in audit `after_json` — no credentials, only catalog id, database name,
 * mode, and the non-secret server label.
 */
final readonly class ProvisionedAppDatabase
{
    public function __construct(
        public string $catalogId,
        public string $databaseName,
        public DatabaseTargetMode $mode = DatabaseTargetMode::Provision,
        public ?string $server = null,
    ) {
    }
}
