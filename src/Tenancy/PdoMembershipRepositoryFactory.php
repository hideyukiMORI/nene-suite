<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoMembershipRepositoryFactory implements MembershipRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): MembershipRepositoryInterface
    {
        return new PdoMembershipRepository($query);
    }
}
