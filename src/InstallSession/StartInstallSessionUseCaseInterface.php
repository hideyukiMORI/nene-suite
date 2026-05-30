<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

interface StartInstallSessionUseCaseInterface
{
    public function execute(StartInstallSessionInput $input): StartInstallSessionOutput;
}
