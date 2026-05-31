<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface CreateOperatorUseCaseInterface
{
    public function execute(CreateOperatorInput $input): CreateOperatorOutput;
}
