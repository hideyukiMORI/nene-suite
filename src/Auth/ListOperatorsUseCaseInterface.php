<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface ListOperatorsUseCaseInterface
{
    public function execute(): ListOperatorsOutput;
}
