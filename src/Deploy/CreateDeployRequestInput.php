<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class CreateDeployRequestInput
{
    public function __construct(
        public string $service,
        public string $imageDigest,
        public string $operatorId,
        public ?string $requestId = null,
    ) {
    }
}
