<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Hashes and verifies operator passwords with PHP's password_* API (bcrypt by
 * default). Plaintext passwords are never stored or logged.
 */
final readonly class PasswordHasher
{
    /** Bcrypt hash of an empty string, used for constant-work verification of unknown emails. */
    private const DUMMY_HASH = '$2y$10$usesomesillystringforsalt0aQ4Z0Q5Yk5pXpXqXqXqXqXqXqXqXq';

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Performs verification work against a dummy hash so that authenticating an
     * unknown email takes a comparable amount of time (mitigates user enumeration).
     */
    public function verifyDummy(string $plain): void
    {
        password_verify($plain, self::DUMMY_HASH);
    }
}
