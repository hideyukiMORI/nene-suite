<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoFederationSigningKeyRepositoryFactory implements FederationSigningKeyRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): FederationSigningKeyRepositoryInterface
    {
        return new PdoFederationSigningKeyRepository($query);
    }
}
