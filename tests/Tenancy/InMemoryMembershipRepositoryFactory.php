<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\Tenancy\MembershipRepositoryFactoryInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;

/**
 * Returns a shared in-memory repository, ignoring the executor — there is no real
 * transaction in unit tests.
 */
final readonly class InMemoryMembershipRepositoryFactory implements MembershipRepositoryFactoryInterface
{
    public function __construct(
        private MembershipRepositoryInterface $repository,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): MembershipRepositoryInterface
    {
        return $this->repository;
    }
}
