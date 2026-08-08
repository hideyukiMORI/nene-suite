<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginGenWatermarkCoordinate;
use NeNeSuite\Origin\OriginGenWatermarkRepositoryInterface;

/**
 * In-memory {@see OriginGenWatermarkRepositoryInterface} for tests — same monotonic, per-coordinate
 * semantics as the PDO store, without a database.
 */
final class InMemoryOriginGenWatermarkRepository implements OriginGenWatermarkRepositoryInterface
{
    /** @var array<string, int> */
    private array $watermarks = [];

    public function current(OriginGenWatermarkCoordinate $coordinate): ?int
    {
        return $this->watermarks[$coordinate->key] ?? null;
    }

    public function record(OriginGenWatermarkCoordinate $coordinate, int $gen, string $now): void
    {
        if ($gen > ($this->watermarks[$coordinate->key] ?? 0)) {
            $this->watermarks[$coordinate->key] = $gen;
        }
    }
}
