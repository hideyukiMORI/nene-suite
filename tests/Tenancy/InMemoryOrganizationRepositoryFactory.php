<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryFactoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;

/**
 * Returns a shared in-memory repository, ignoring the executor — there is no real
 * transaction in unit tests.
 */
final readonly class InMemoryOrganizationRepositoryFactory implements OrganizationRepositoryFactoryInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): OrganizationRepositoryInterface
    {
        return $this->repository;
    }
}
