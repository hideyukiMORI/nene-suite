<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use RuntimeException;

/**
 * Raised when a result is reported for a request that is already terminal — the agent must
 * never re-execute a reported request. Mapped to HTTP 409 `deploy-request-conflict`.
 */
final class DeployRequestConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $deployRequestId,
    ) {
        parent::__construct("Deploy request '{$deployRequestId}' already has a terminal result.");
    }
}
