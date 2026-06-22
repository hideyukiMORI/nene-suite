<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when revoking a federation signing key by a `kid` that does not exist (B1.8). Operator
 * CLI only — no HTTP surface.
 */
final class FederationSigningKeyNotFoundException extends RuntimeException
{
    public function __construct(string $kid)
    {
        parent::__construct(sprintf('No federation signing key with kid "%s".', $kid));
    }
}
