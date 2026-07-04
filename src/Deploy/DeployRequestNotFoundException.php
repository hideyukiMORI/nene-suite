<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use RuntimeException;

/**
 * Raised when a deploy request id does not resolve to a row. Mapped to HTTP 404.
 */
final class DeployRequestNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $deployRequestId,
    ) {
        parent::__construct("Deploy request '{$deployRequestId}' was not found.");
    }
}
