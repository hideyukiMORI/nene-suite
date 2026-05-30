<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class GetAuthSessionUseCase implements GetAuthSessionUseCaseInterface
{
    public function __construct(
        private OperatorRepositoryInterface $operators,
    ) {
    }

    public function execute(string $operatorId): Operator
    {
        $operator = $this->operators->findById($operatorId);

        if ($operator === null) {
            // Token was valid but the operator was removed — treat as unauthenticated.
            throw new UnauthorizedException();
        }

        return $operator;
    }
}
