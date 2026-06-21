<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when a membership change would break a registry invariant — revoking or
 * demoting the last platform `superadmin`, or the last `admin` of an organization.
 * Mapped to HTTP 409 once the superadmin surface is added (milestone A7).
 */
final class MembershipInvariantException extends RuntimeException
{
    public function __construct(
        public readonly string $detail,
    ) {
        parent::__construct($detail);
    }
}
