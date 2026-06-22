<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface ListSessionOrganizationsUseCaseInterface
{
    public function execute(string $operatorId): ListSessionOrganizationsOutput;
}
