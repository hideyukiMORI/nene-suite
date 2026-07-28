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

    /**
     * @param list<string> $warnings per-mirror failover notices (mirrors.md §4.2) when the walk was
     *                               attempted against more than one base
     */
    public static function unavailable(
        OriginFeedQuery $query,
        string $servedLocale,
        string $reason,
        ?OriginFreshnessState $freshness = null,
        array $warnings = [],
    ): self {
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
            warnings: $warnings,
        );
    }

    /**
     * The same feed with `$warnings` prepended — used to carry mirror-failover notices outward.
     *
     * @param list<string> $warnings
     */
    public function withWarningsPrepended(array $warnings): self
    {
        if ($warnings === []) {
            return $this;
        }

        return new self(
            product: $this->product,
            audience: $this->audience,
            kind: $this->kind,
            requestedLocale: $this->requestedLocale,
            servedLocale: $this->servedLocale,
            available: $this->available,
            count: $this->count,
            items: $this->items,
            freshness: $this->freshness,
            reason: $this->reason,
            warnings: [...$warnings, ...$this->warnings],
        );
    }
}
