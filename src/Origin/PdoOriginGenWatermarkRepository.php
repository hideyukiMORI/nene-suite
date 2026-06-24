<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Throwable;

/**
 * Control-DB store for the per-product gen watermark. Monotonic by construction: `record()` advances
 * only when the new generation is strictly higher, and the UPDATE carries a `gen < ?` guard so a
 * concurrent advance can never regress the watermark (best-effort, no transaction needed).
 */
final readonly class PdoOriginGenWatermarkRepository implements OriginGenWatermarkRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function current(string $product): ?int
    {
        $row = $this->query->fetchOne('SELECT gen FROM origin_gen_watermarks WHERE product = ?', [$product]);

        if ($row === null) {
            return null;
        }

        return (int) ($row['gen'] ?? 0);
    }

    public function record(string $product, int $gen, string $now): void
    {
        $row = $this->query->fetchOne('SELECT gen FROM origin_gen_watermarks WHERE product = ?', [$product]);

        if ($row === null) {
            try {
                $this->query->execute(
                    'INSERT INTO origin_gen_watermarks (product, gen, updated_at) VALUES (?, ?, ?)',
                    [$product, $gen, $now],
                );

                return;
            } catch (Throwable) {
                // Lost a concurrent first-insert race — re-read and fall through to the monotonic
                // advance rather than dropping this generation.
                $row = $this->query->fetchOne('SELECT gen FROM origin_gen_watermarks WHERE product = ?', [$product]);

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
                'UPDATE origin_gen_watermarks SET gen = ?, updated_at = ? WHERE product = ? AND gen < ?',
                [$gen, $now, $product, $gen],
            );
        }
    }
}
