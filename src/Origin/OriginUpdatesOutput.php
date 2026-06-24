<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The update-check result for the whole roster. `available` is false when the Origin client is not
 * configured (no `NENE_ORIGIN_URL` or no embedded trust anchor) — the dashboard then shows the
 * Phase-B placeholder rather than fabricated data; `updates` is empty in that case.
 */
final readonly class OriginUpdatesOutput
{
    /**
     * @param list<OriginUpdateSignal> $updates
     */
    public function __construct(
        public bool $available,
        public array $updates,
    ) {
    }

    public static function disabled(): self
    {
        return new self(false, []);
    }

    /**
     * @param list<OriginUpdateSignal> $updates
     */
    public static function enabled(array $updates): self
    {
        return new self(true, $updates);
    }
}
