<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when a bearer token is missing, malformed, or invalid on an
 * authenticated endpoint. Mapped to 401 `unauthorized`.
 */
final class UnauthorizedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A valid apex operator token is required.');
    }
}
