<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

interface WriteEnvConfigUseCaseInterface
{
    public function execute(WriteEnvConfigInput $input): WriteEnvConfigOutput;
}
