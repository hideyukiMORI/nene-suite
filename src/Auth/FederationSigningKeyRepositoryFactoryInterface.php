<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Builds a {@see FederationSigningKeyRepositoryInterface} bound to a specific query executor, so a
 * write use case can construct it inside `DatabaseTransactionManagerInterface::transactional()` and
 * have its writes (and the paired audit row) commit/roll back together (ADR 0007 §5 —
 * connection-per-transaction; see {@see OperatorRepositoryFactoryInterface}).
 */
interface FederationSigningKeyRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): FederationSigningKeyRepositoryInterface;
}
