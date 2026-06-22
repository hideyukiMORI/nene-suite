<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class GenerateFederationSigningKeyInput
{
    public function __construct(
        public ?string $requestId = null,
    ) {
    }
}
