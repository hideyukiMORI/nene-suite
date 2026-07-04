<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

interface CreateDeployRequestUseCaseInterface
{
    /**
     * @throws DeployCapabilityDisabledException while the capability flag is off
     * @throws DeployValidationException on a non-catalog service or malformed digest
     */
    public function execute(CreateDeployRequestInput $input): CreateDeployRequestOutput;
}
