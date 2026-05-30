<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class CreateAuthSessionOutput
{
    public function __construct(
        public string $token,
        public int $expiresAt,
        public Operator $operator,
    ) {
    }
}
