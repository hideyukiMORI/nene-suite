<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class CreateOperatorInput
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $displayName = null,
        public ?string $requestId = null,
    ) {
    }
}
