<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class ReportDeployResultInput
{
    public function __construct(
        public string $deployRequestId,
        public DeployRequestStatus $status,
        public ?string $detail,
        public ?string $requestId = null,
    ) {
    }
}
