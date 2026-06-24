<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The content-tree feed result across the installed roster (announcements or house-ads). `available`
 * is false when the Origin client is not configured (no URL / no trust anchor) — the dashboard shows
 * the Phase-B placeholder, not fabricated data; `feeds` is empty in that case. Each entry is one
 * product's verified {@see OriginFeed} (which itself may be unavailable per product).
 */
final readonly class OriginFeedsOutput
{
    /**
     * @param list<OriginFeed> $feeds
     */
    public function __construct(
        public bool $available,
        public array $feeds,
    ) {
    }

    public static function disabled(): self
    {
        return new self(false, []);
    }

    /**
     * @param list<OriginFeed> $feeds
     */
    public static function enabled(array $feeds): self
    {
        return new self(true, $feeds);
    }
}
