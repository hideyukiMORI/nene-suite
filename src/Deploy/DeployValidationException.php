<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use RuntimeException;

/**
 * Raised when deploy request input fails domain validation — an unknown (non-catalog)
 * service or a malformed image digest. Mapped to HTTP 422.
 */
final class DeployValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $detail,
    ) {
        parent::__construct("Deploy request validation failed for field '{$field}': {$detail}");
    }
}
