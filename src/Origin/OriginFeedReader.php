<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use JsonException;

/**
 * Fetches + verifies one content-tree feed and returns its trusted items. Uses the **snapshot path**
 * (not the §2.4 reduction): a `(product, audience, locale)` current can delegate both an
 * announcement and an ad leaf, and only the snapshot's `targets` map selects a specific `kind`. On
 * accept the feed-targets leaf carries `count` + `content_sha256`; the feed body is re-read and
 * re-hashed before its items are trusted. Missing-locale (`404` → unreachable) falls back to `en`
 * (ja/en are always published); a published-empty feed (`count=0`) stays available with no items.
 */
final readonly class OriginFeedReader
{
    private const string FALLBACK_LOCALE = 'en';

    public function __construct(
        private OriginReadModelVerifier $verifier,
    ) {
    }

    public function read(
        OriginFeedQuery $query,
        OriginObjectStoreProvider $stores,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): OriginFeed {
        $feed = $this->readLocale($query, $query->locale, $stores, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);

        // Missing locale variant (404 → unreachable) falls back to en — after every mirror has been
        // tried for the requested locale (mirrors serve byte-identical objects, so an absent variant
        // is absent everywhere); a verification failure does not fall back.
        if (!$feed->available && $feed->reason === 'origin_unreachable' && $query->locale !== self::FALLBACK_LOCALE) {
            return $this->readLocale($query, self::FALLBACK_LOCALE, $stores, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);
        }

        return $feed;
    }

    /**
     * One locale, walked in mirror order (mirrors.md §4): the first mirror whose walk fully verifies
     * wins; transport failures and verification rejects alike move to the next base, each surfaced
     * as a warning. The whole walk — `current` → `feed-targets` → **feed body** — runs against a
     * single store, so a body is never paired with another mirror's targets (§4.1).
     */
    private function readLocale(
        OriginFeedQuery $query,
        string $locale,
        OriginObjectStoreProvider $stores,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): OriginFeed {
        $mirrorWarnings = [];
        $last = null;

        foreach ($stores->stores() as $baseUrl => $store) {
            $feed = $this->readLocaleFrom($query, $locale, $store, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);

            if ($feed->available) {
                return $feed->withWarningsPrepended($mirrorWarnings);
            }

            $mirrorWarnings[] = sprintf('mirror failover: %s skipped (%s)', $baseUrl, $feed->reason ?? 'unknown');
            $last = $feed;
        }

        $last ??= OriginFeed::unavailable($query, $locale, 'origin_not_configured');

        return $last->withWarningsPrepended($mirrorWarnings);
    }

    private function readLocaleFrom(
        OriginFeedQuery $query,
        string $locale,
        OriginObjectStore $store,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): OriginFeed {
        $persistedGen = $watermarks->current($query->product) ?? $genFloor;
        $client = new OriginClientState($persistedGen, $rootVersionFloor, $genFloor, $now);
        $request = new OriginVerificationRequest(
            OriginTreeKind::Feed,
            sprintf('v1/feeds/%s/%s/%s/current', $query->product, $query->audience, $locale),
            sprintf('%s/%s/%s/%s', $query->product, $query->audience, $locale, $query->kind->value),
            false,
        );

        try {
            $outcome = $this->verifier->verify($store, $anchor, $client, $request);
        } catch (OriginUnreachableException) {
            return OriginFeed::unavailable($query, $locale, 'origin_unreachable');
        }

        if (!$outcome->accepted) {
            return OriginFeed::unavailable($query, $locale, $outcome->reason->value, $outcome->freshness);
        }

        $leaf = $outcome->leaf ?? [];
        $count = is_int($leaf['count'] ?? null) ? $leaf['count'] : 0;

        if ($count === 0) {
            return OriginFeed::available($query, $locale, 0, [], $outcome->freshness, $outcome->warnings);
        }

        $contentSha = is_string($leaf['content_sha256'] ?? null) ? $leaf['content_sha256'] : '';

        try {
            $bodyBytes = $store->readByHash('feed-bodies', $contentSha);
        } catch (OriginUnreachableException) {
            return OriginFeed::unavailable($query, $locale, 'origin_unreachable', $outcome->freshness);
        }

        if (hash('sha256', $bodyBytes) !== $contentSha) {
            return OriginFeed::unavailable($query, $locale, 'content_hash_mismatch', $outcome->freshness);
        }

        $items = $this->decodeItems($bodyBytes);
        if ($items === null) {
            return OriginFeed::unavailable($query, $locale, 'malformed_object', $outcome->freshness);
        }

        return OriginFeed::available($query, $locale, $count, $items, $outcome->freshness, $outcome->warnings);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function decodeItems(string $bytes): ?array
    {
        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                return null;
            }

            /** @var array<string, mixed> $item */
            $items[] = $item;
        }

        return $items;
    }
}
