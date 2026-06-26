<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use RuntimeException;

/**
 * Raised when an app is configured for `provision` mode on an external server. In the
 * Tier B MVP, provisioning is suite-server only and external servers are **adopt-only**
 * (ADR 0021 OQ2 — least privilege: the suite never holds a CREATE credential for a
 * server it does not own). The target model admits external provision later without a
 * contract change.
 */
final class ExternalProvisionNotSupportedException extends RuntimeException
{
    public function __construct(string $catalogId)
    {
        parent::__construct(
            "External-server provisioning is not supported for '{$catalogId}' "
            . '(ADR 0021 OQ2 — external servers are adopt-only). '
            . 'Set NENE_SUITE_APP_{SNAKE}_DB_MODE=adopt, or remove its _DB_SERVER override to use the suite server.',
        );
    }
}
