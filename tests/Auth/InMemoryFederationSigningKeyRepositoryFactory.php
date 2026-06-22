<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\Auth\FederationSigningKeyRepositoryFactoryInterface;
use NeNeSuite\Auth\FederationSigningKeyRepositoryInterface;

final readonly class InMemoryFederationSigningKeyRepositoryFactory implements FederationSigningKeyRepositoryFactoryInterface
{
    public function __construct(
        private InMemoryFederationSigningKeyRepository $repository,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): FederationSigningKeyRepositoryInterface
    {
        return $this->repository;
    }
}
