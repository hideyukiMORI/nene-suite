<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class ListDeployRequestsOutput
{
    /**
     * @param list<DeployRequest> $requests
     */
    public function __construct(
        public bool $enabled,
        public array $requests,
    ) {
    }
}
