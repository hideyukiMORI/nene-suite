<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * Records the outcome of provisioning one app's database.
 * Safe to include in audit `after_json` — no credentials, only catalog id and name.
 */
final readonly class ProvisionedAppDatabase
{
    public function __construct(
        public string $catalogId,
        public string $databaseName,
    ) {
    }
}
