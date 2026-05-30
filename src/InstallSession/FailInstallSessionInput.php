<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class FailInstallSessionInput
{
    public function __construct(
        public string $installSessionId,
        public string $failureCode,
        public ?string $reason = null,
        public ?string $requestId = null,
    ) {
    }
}
