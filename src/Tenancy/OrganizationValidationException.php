<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when organization input fails domain validation (name, slug format).
 * Mapped to HTTP 422 once the superadmin surface is added (milestone A7).
 */
final class OrganizationValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $detail,
    ) {
        parent::__construct("Organization validation failed for field '{$field}': {$detail}");
    }
}
