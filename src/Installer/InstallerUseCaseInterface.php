<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

interface InstallerUseCaseInterface
{
    public function execute(InstallerInput $input): InstallerOutput;
}
