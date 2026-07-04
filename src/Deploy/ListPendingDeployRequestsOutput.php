<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class ListPendingDeployRequestsOutput
{
    /**
     * @param list<DeployRequest> $requests
     */
    public function __construct(
        public array $requests,
    ) {
    }
}
