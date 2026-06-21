<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Builds an {@see OperatorRepositoryInterface} bound to a specific query executor.
 *
 * A write use case constructs the repository inside
 * {@see \Nene2\Database\DatabaseTransactionManagerInterface::transactional()} using
 * the transaction's executor, so the repository's writes participate in that
 * transaction (NENE2's connection-per-transaction model — a repository injected at
 * construction time uses a different connection and is not rolled back).
 */
interface OperatorRepositoryFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): OperatorRepositoryInterface;
}
