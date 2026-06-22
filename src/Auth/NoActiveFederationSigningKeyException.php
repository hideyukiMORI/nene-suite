<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when rotating but there is no active federation signing key to rotate from. Generate the
 * first key with `ops/keys/generate-federation-key.php` before rotating (B1.8). Operator CLI only.
 */
final class NoActiveFederationSigningKeyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No active federation signing key to rotate; generate one first.');
    }
}
