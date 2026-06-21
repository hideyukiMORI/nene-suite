<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Support;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;

/**
 * Test double that runs the unit of work immediately, with no real transaction,
 * passing a no-op executor to the callback. Use it to unit-test transactional use
 * cases against in-memory repositories/recorders (which ignore the executor). For
 * real commit/rollback behaviour, use {@see \Nene2\Testing\DatabaseTestKit} with
 * the shipped PDO transaction manager instead.
 */
final readonly class ImmediateTransactionManager implements DatabaseTransactionManagerInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $executor = new NullDatabaseQueryExecutor(),
    ) {
    }

    public function transactional(callable $callback): mixed
    {
        return $callback($this->executor);
    }
}
