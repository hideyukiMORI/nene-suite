<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised when generating a federation signing key while one is already `active`. Exactly one
 * active key may exist at a time; replacing it is a deliberate **rotation** (milestone B1.8), not
 * a second generate. There is no HTTP surface — the key-gen command is operator-run CLI.
 */
final class ActiveFederationSigningKeyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An active federation signing key already exists; rotate it instead of generating a new one.');
    }
}
