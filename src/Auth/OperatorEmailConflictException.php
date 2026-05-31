<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when an operator with the given email already exists. Mapped to HTTP 409.
 */
final class OperatorEmailConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $email,
    ) {
        parent::__construct("An operator with email '{$email}' already exists.");
    }
}
