<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\Auth\OperatorRepositoryFactoryInterface;
use NeNeSuite\Auth\OperatorRepositoryInterface;

/**
 * Returns a shared in-memory repository, ignoring the executor — there is no real
 * transaction in unit tests.
 */
final readonly class InMemoryOperatorRepositoryFactory implements OperatorRepositoryFactoryInterface
{
    public function __construct(
        private OperatorRepositoryInterface $repository,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): OperatorRepositoryInterface
    {
        return $this->repository;
    }
}
