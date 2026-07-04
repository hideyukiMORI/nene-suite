<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

interface ListDeployRequestsUseCaseInterface
{
    public function execute(ListDeployRequestsInput $input): ListDeployRequestsOutput;
}
