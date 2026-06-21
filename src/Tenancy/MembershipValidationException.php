<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when a membership's role and scope are inconsistent — a `superadmin` must be
 * platform-level (`organizationId` null) and every other role must be scoped to an
 * organization (`Role::requiresOrganization()`). Mapped to HTTP 422 once the
 * superadmin surface is added (milestone A7).
 */
final class MembershipValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $detail,
    ) {
        parent::__construct("Membership validation failed for field '{$field}': {$detail}");
    }
}
