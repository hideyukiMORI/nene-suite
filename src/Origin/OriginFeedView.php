<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * camelCase HTTP representation of an {@see OriginFeed} (docs/openapi/openapi.yaml OriginFeed
 * schema). `items` is the verified feed body, passed through as-is (announcement / ad objects).
 */
final readonly class OriginFeedView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(OriginFeed $feed): array
    {
        return [
            'product' => $feed->product,
            'audience' => $feed->audience,
            'kind' => $feed->kind->value,
            'requestedLocale' => $feed->requestedLocale,
            'servedLocale' => $feed->servedLocale,
            'available' => $feed->available,
            'count' => $feed->count,
            'items' => $feed->items,
            'freshness' => $feed->freshness?->value,
            'reason' => $feed->reason,
            'warnings' => $feed->warnings,
        ];
    }
}
