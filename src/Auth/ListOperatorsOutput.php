<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class ListOperatorsOutput
{
    /**
     * @param list<Operator> $operators
     */
    public function __construct(
        public array $operators,
    ) {
    }
}
