<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Support;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * No-op query executor for unit tests that drive transactional use cases against
 * in-memory repositories/recorders. The in-memory doubles ignore the executor, so
 * none of these methods are expected to perform I/O.
 */
final readonly class NullDatabaseQueryExecutor implements DatabaseQueryExecutorInterface
{
    /**
     * @param array<string|int, string|int|float|bool|null> $parameters
     */
    public function execute(string $sql, array $parameters = []): int
    {
        return 0;
    }

    /**
     * @param array<string|int, string|int|float|bool|null> $parameters
     */
    public function insert(string $sql, array $parameters = []): int
    {
        return 0;
    }

    public function lastInsertId(): int
    {
        return 0;
    }

    /**
     * @param array<string|int, string|int|float|bool|null> $parameters
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $parameters = []): ?array
    {
        return null;
    }

    /**
     * @param array<string|int, string|int|float|bool|null> $parameters
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $parameters = []): array
    {
        return [];
    }
}
