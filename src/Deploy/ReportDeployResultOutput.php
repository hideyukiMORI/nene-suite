<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

final readonly class ReportDeployResultOutput
{
    public function __construct(
        public DeployRequest $request,
    ) {
    }
}
