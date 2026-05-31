<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class CreateOperatorOutput
{
    public function __construct(
        public Operator $operator,
    ) {
    }
}
