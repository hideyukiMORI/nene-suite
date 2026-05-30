<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised on a failed login (unknown email or wrong password). Mapped to 401
 * `invalid-credentials` with a single message that does not reveal which field
 * was wrong.
 */
final class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}
