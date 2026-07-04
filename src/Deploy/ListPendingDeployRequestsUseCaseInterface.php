<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

interface ListPendingDeployRequestsUseCaseInterface
{
    public function execute(): ListPendingDeployRequestsOutput;
}
