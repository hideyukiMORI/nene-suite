<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface SwitchActiveOrganizationUseCaseInterface
{
    public function execute(SwitchActiveOrganizationInput $input): CreateAuthSessionOutput;
}
