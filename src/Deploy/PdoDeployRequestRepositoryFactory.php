<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoDeployRequestRepositoryFactory implements DeployRequestRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): DeployRequestRepositoryInterface
    {
        return new PdoDeployRequestRepository($query);
    }
}
