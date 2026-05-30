<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class CompleteInstallSessionInput
{
    public function __construct(
        public string $installSessionId,
        public ?string $requestId = null,
    ) {
    }
}
