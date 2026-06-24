<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * A resolved content-tree feed. On `available`, `items` is the **verified** feed body (the array of
 * announcement / ad objects whose bytes matched the signed `content_sha256`); `count=0` is a
 * published-empty feed (available, no fallback). On unavailable, `reason` carries the stable
 * verification reason (or `origin_unreachable`). `servedLocale` is the locale actually returned
 * (may differ from the request after the `en` fallback).
 */
final readonly class OriginFeed
{
    /**
     * @param list<array<string, mixed>> $items
     * @param list<string>               $warnings
     */
    public function __construct(
        public string $product,
        public string $audience,
        public OriginFeedKind $kind,
        public string $requestedLocale,
        public string $servedLocale,
        public bool $available,
        public int $count,
        public array $items = [],
        public ?OriginFreshnessState $freshness = null,
        public ?string $reason = null,
        public array $warnings = [],
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param list<string>               $warnings
     */
    public static function available(OriginFeedQuery $query, string $servedLocale, int $count, array $items, ?OriginFreshnessState $freshness, array $warnings = []): self
    {
        return new self(
            product: $query->product,
            audience: $query->audience,
            kind: $query->kind,
            requestedLocale: $query->locale,
            servedLocale: $servedLocale,
            available: true,
            count: $count,
            items: $items,
            freshness: $freshness,
            warnings: $warnings,
        );
    }

    public static function unavailable(OriginFeedQuery $query, string $servedLocale, string $reason, ?OriginFreshnessState $freshness = null): self
    {
        return new self(
            product: $query->product,
            audience: $query->audience,
            kind: $query->kind,
            requestedLocale: $query->locale,
            servedLocale: $servedLocale,
            available: false,
            count: 0,
            freshness: $freshness,
            reason: $reason,
        );
    }
}
