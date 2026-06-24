<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Persists the profiled-TUF anti-rollback watermark — the highest `gen` ever accepted per product
 * (ADR 0017 §5). Read to build {@see OriginClientState} before verifying a tree; advanced after a
 * verified generation is accepted. Product-scoped and cross-channel: `gen` is one counter across
 * channels, so the watermark is keyed by product slug alone.
 */
interface OriginGenWatermarkRepositoryInterface
{
    /** The persisted highest-accepted generation for `$product`, or null when none is recorded. */
    public function current(string $product): ?int;

    /**
     * Advance the watermark to `$gen` when it is higher than the stored value (monotonic — a lower
     * or equal `$gen` is a no-op, so the watermark never regresses). `$now` is the ISO-8601 UTC
     * advance time.
     */
    public function record(string $product, int $gen, string $now): void;
}
