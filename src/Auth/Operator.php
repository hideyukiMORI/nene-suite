<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Apex operator. `passwordHash` is a bcrypt/argon hash — never the plaintext,
 * and never exposed over HTTP, in audit, or in the manifest.
 */
final readonly class Operator
{
    public function __construct(
        public string $id,
        public string $email,
        public string $passwordHash,
        public ?string $displayName,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
