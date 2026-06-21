<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOperatorRepositoryFactory implements OperatorRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): OperatorRepositoryInterface
    {
        return new PdoOperatorRepository($query);
    }
}
