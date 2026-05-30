<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface GetAuthSessionUseCaseInterface
{
    /**
     * @throws UnauthorizedException when the operator no longer exists
     */
    public function execute(string $operatorId): Operator;
}
