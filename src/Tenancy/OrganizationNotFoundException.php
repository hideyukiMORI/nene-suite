<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when an organization id does not resolve to a registry row. Mapped to
 * HTTP 404 once the superadmin surface is added (milestone A7).
 */
final class OrganizationNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $organizationId,
    ) {
        parent::__construct("Organization '{$organizationId}' was not found.");
    }
}
