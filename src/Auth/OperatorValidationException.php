<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when operator creation input fails domain validation (email format,
 * password length). Mapped to HTTP 422 via the exception handler.
 */
final class OperatorValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $detail,
    ) {
        parent::__construct("Operator validation failed for field '{$field}': {$detail}");
    }
}
