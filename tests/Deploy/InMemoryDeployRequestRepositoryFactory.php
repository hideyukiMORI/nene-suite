<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\Deploy\DeployRequestRepositoryFactoryInterface;
use NeNeSuite\Deploy\DeployRequestRepositoryInterface;

/**
 * Returns a shared in-memory repository, ignoring the executor — there is no real
 * transaction in unit tests.
 */
final readonly class InMemoryDeployRequestRepositoryFactory implements DeployRequestRepositoryFactoryInterface
{
    public function __construct(
        private DeployRequestRepositoryInterface $repository,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): DeployRequestRepositoryInterface
    {
        return $this->repository;
    }
}
