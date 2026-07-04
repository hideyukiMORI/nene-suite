<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use RuntimeException;

/**
 * Raised when a machine deploy-seam call carries a missing or mismatched agent key.
 * Mapped to HTTP 401 `deploy-agent-unauthorized`.
 */
final class DeployAgentUnauthorizedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A valid X-NENE-SUITE-DEPLOY-KEY header is required.');
    }
}
