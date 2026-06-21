<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Builds an {@see OrganizationRepositoryInterface} bound to a specific query executor
 * so a use case can construct one inside
 * {@see \Nene2\Database\DatabaseTransactionManagerInterface::transactional()} and have
 * its writes participate in that transaction (see
 * {@see \NeNeSuite\Auth\OperatorRepositoryFactoryInterface} for the rationale).
 */
interface OrganizationRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): OrganizationRepositoryInterface;
}
