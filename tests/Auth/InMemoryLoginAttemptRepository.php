<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\LoginAttemptRepositoryInterface;
use RuntimeException;

final class InMemoryLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    /** @var array<string, array{window: int, count: int}> */
    private array $rows = [];

    public function __construct(
        private readonly bool $throwOnUse = false,
    ) {
    }

    public function countWithinWindow(string $key, int $windowSeconds, int $now): int
    {
        $this->guard();
        $row = $this->rows[$key] ?? null;

        if ($row === null || $now - $row['window'] >= $windowSeconds) {
            return 0;
        }

        return $row['count'];
    }

    public function recordFailure(string $key, int $windowSeconds, int $now): int
    {
        $this->guard();
        $row = $this->rows[$key] ?? null;

        if ($row === null || $now - $row['window'] >= $windowSeconds) {
            $this->rows[$key] = ['window' => $now, 'count' => 1];

            return 1;
        }

        $count = $row['count'] + 1;
        $this->rows[$key] = ['window' => $row['window'], 'count' => $count];

        return $count;
    }

    public function clear(string $key): void
    {
        $this->guard();
        unset($this->rows[$key]);
    }

    private function guard(): void
    {
        if ($this->throwOnUse) {
            throw new RuntimeException('login attempt store is down');
        }
    }
}
