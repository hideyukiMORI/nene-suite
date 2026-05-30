<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class CreateAuthSessionInput
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
