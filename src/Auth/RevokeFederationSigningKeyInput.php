<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class RevokeFederationSigningKeyInput
{
    public function __construct(
        public string $kid,
        public ?string $requestId = null,
    ) {
    }
}
