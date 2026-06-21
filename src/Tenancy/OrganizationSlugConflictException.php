<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when an organization with the given slug already exists. Mapped to HTTP 409
 * once the superadmin surface is added (milestone A7).
 */
final class OrganizationSlugConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $slug,
    ) {
        parent::__construct("An organization with slug '{$slug}' already exists.");
    }
}
