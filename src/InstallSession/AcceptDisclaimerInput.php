<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class AcceptDisclaimerInput
{
    public function __construct(
        public string $installSessionId,
        public string $disclaimerVersion,
        public ?string $acceptedLabel = null,
        public ?string $requestId = null,
    ) {
    }
}
