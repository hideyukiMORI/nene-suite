<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;

interface DeployRequestRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): DeployRequestRepositoryInterface;
}
