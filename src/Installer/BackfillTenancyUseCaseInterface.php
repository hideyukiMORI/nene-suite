<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

interface BackfillTenancyUseCaseInterface
{
    public function execute(BackfillTenancyInput $input): BackfillTenancyOutput;
}
