<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when a client IP exceeds the login rate-limit (Phase B1.2). Mapped to HTTP 429 with a
 * `Retry-After` header by {@see LoginRateLimitedExceptionHandler}. Carries no account information.
 */
final class LoginRateLimitedException extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many login attempts.');
    }
}
