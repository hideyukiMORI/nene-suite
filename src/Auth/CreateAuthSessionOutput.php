<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\Role;

final readonly class CreateAuthSessionOutput
{
    public function __construct(
        public string $token,
        public int $expiresAt,
        public Operator $operator,
        public ?string $orgExternalId,
        public ?Role $role,
        public bool $superadmin,
    ) {
    }
}
