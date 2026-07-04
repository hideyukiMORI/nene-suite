<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use DateTimeImmutable;

interface ComputeDeployPlanUseCaseInterface
{
    public function execute(DateTimeImmutable $now): DeployPlan;
}
