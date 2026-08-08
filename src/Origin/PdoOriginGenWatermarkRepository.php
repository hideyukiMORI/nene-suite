<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Throwable;

/**
 * Control-DB store for the per-coordinate gen watermark. Monotonic by construction: `record()`
 * advances only when the new generation is strictly higher, and the UPDATE carries a `gen < ?` guard
 * so a concurrent advance can never regress the watermark (best-effort, no transaction needed).
 *
 * One row per {@see OriginGenWatermarkCoordinate}, not per product — see that class for why sharing
 * a row across trees fails closed (suite #424).
 */
final readonly class PdoOriginGenWatermarkRepository implements OriginGenWatermarkRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function current(OriginGenWatermarkCoordinate $coordinate): ?int
    {
        $row = $this->fetch($coordinate);

        if ($row === null) {
            return null;
        }

        return (int) ($row['gen'] ?? 0);
    }

    public function record(OriginGenWatermarkCoordinate $coordinate, int $gen, string $now): void
    {
        $row = $this->fetch($coordinate);

        if ($row === null) {
            try {
                $this->query->execute(
                    'INSERT INTO origin_gen_watermarks (coordinate, gen, updated_at) VALUES (?, ?, ?)',
                    [$coordinate->key, $gen, $now],
                );

                return;
            } catch (Throwable) {
                // Lost a concurrent first-insert race — re-read and fall through to the monotonic
                // advance rather than dropping this generation.
                $row = $this->fetch($coordinate);

                if ($row === null) {
                    return;
                }
            }
        }

        $stored = (int) ($row['gen'] ?? 0);

        if ($gen > $stored) {
            // The `gen < ?` guard makes the advance idempotent and race-safe: it only ever moves the
            // watermark forward, never backward.
            $this->query->execute(
                'UPDATE origin_gen_watermarks SET gen = ?, updated_at = ? WHERE coordinate = ? AND gen < ?',
                [$gen, $now, $coordinate->key, $gen],
            );
        }
    }

    /** @return array<string, mixed>|null */
    private function fetch(OriginGenWatermarkCoordinate $coordinate): ?array
    {
        return $this->query->fetchOne(
            'SELECT gen FROM origin_gen_watermarks WHERE coordinate = ?',
            [$coordinate->key],
        );
    }
}
