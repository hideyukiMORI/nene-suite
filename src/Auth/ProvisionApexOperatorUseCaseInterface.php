<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface ProvisionApexOperatorUseCaseInterface
{
    public function execute(CreateOperatorInput $input): CreateOperatorOutput;
}
