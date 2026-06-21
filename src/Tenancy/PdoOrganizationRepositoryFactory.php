<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOrganizationRepositoryFactory implements OrganizationRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): OrganizationRepositoryInterface
    {
        return new PdoOrganizationRepository($query);
    }
}
