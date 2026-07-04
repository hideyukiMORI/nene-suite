<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class ListDeployRequestsInput
{
    public function __construct(
        public ?DeployRequestStatus $status,
        public int $limit,
    ) {
    }
}
