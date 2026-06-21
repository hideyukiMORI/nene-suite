<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when a membership id does not resolve to a row. Mapped to HTTP 404 once the
 * superadmin surface is added (milestone A7).
 */
final class MembershipNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $membershipId,
    ) {
        parent::__construct("Membership '{$membershipId}' was not found.");
    }
}
