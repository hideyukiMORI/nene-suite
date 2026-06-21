<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when an authenticated operator lacks the platform `superadmin` privilege required
 * by a cross-tenant management endpoint (milestone A7). Mapped to HTTP 403.
 */
final class ForbiddenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Platform superadmin privileges are required.');
    }
}
