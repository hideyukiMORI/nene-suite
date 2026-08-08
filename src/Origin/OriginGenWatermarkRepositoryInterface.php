<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Persists the profiled-TUF anti-rollback watermark — the highest `gen` ever accepted at a given
 * tree coordinate (ADR 0017 §5). Read to build {@see OriginClientState} before verifying a tree;
 * advanced after a verified generation is accepted.
 *
 * Keyed by {@see OriginGenWatermarkCoordinate}, never by product alone: Origin numbers `gen`
 * independently per coordinate, so a watermark from one tree is not a floor for another (see that
 * class for the failure this prevents — suite #424).
 */
interface OriginGenWatermarkRepositoryInterface
{
    /** The persisted highest-accepted generation at `$coordinate`, or null when none is recorded. */
    public function current(OriginGenWatermarkCoordinate $coordinate): ?int;

    /**
     * Advance the watermark at `$coordinate` to `$gen` when it is higher than the stored value
     * (monotonic — a lower or equal `$gen` is a no-op, so the watermark never regresses). `$now` is
     * the ISO-8601 UTC advance time.
     */
    public function record(OriginGenWatermarkCoordinate $coordinate, int $gen, string $now): void;
}
