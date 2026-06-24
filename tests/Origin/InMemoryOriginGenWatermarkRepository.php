<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginGenWatermarkRepositoryInterface;

/**
 * In-memory {@see OriginGenWatermarkRepositoryInterface} for tests — same monotonic semantics as the
 * PDO store, without a database.
 */
final class InMemoryOriginGenWatermarkRepository implements OriginGenWatermarkRepositoryInterface
{
    /** @var array<string, int> */
    private array $watermarks = [];

    public function current(string $product): ?int
    {
        return $this->watermarks[$product] ?? null;
    }

    public function record(string $product, int $gen, string $now): void
    {
        if ($gen > ($this->watermarks[$product] ?? 0)) {
            $this->watermarks[$product] = $gen;
        }
    }
}
