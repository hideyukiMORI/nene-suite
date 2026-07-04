<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

interface ReportDeployResultUseCaseInterface
{
    /**
     * @throws DeployRequestNotFoundException when the id resolves to no row (404)
     * @throws DeployRequestConflictException when the request is already terminal (409)
     * @throws DeployValidationException when the reported status is not terminal (422)
     */
    public function execute(ReportDeployResultInput $input): ReportDeployResultOutput;
}
