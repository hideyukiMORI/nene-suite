<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Lists every apex operator (oldest first). Read-only — no transaction, no audit. The superadmin
 * membership console consumes it to drive the operator picker (no plaintext or secret material
 * leaves the server; see {@see OperatorView}).
 */
final readonly class ListOperatorsUseCase implements ListOperatorsUseCaseInterface
{
    public function __construct(
        private OperatorRepositoryInterface $operators,
    ) {
    }

    public function execute(): ListOperatorsOutput
    {
        return new ListOperatorsOutput($this->operators->all());
    }
}
