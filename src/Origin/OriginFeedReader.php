<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use DateTimeZone;
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
        $attempt = $this->readLocale($query, $query->locale, $stores, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);
        $feed = $attempt['feed'];

        if ($feed->available || $query->locale === self::FALLBACK_LOCALE) {
            return $feed;
        }

        // Missing locale variant (404 → unreachable) falls back to en — after every mirror has been
        // tried for the requested locale (mirrors serve byte-identical objects, so an absent variant
        // is absent everywhere); a verification failure does not fall back.
        //
        // Order-independence (mirrors.md §4.3): decide from the **set** of attempt results, never
        // from whichever base happened to fail last. Reading only the last reason makes the outcome
        // depend on list order — one degraded mirror answering REJECT next to another's honest 404
        // would silently suppress the fallback, and swapping the two mirrors would restore it.
        if (!in_array('origin_unreachable', $attempt['reasons'], true)) {
            return $feed;
        }

        $fallback = $this->readLocale($query, self::FALLBACK_LOCALE, $stores, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks)['feed'];

        // Carry forward only what the fallback cycle did not already say. Without this, a mirror
        // that REJECTED the requested locale vanishes from the operator's view precisely when it
        // matters — the fallback is the path that hides a degraded mirror behind a working result.
        // Warnings the en cycle repeats verbatim (both cycles hit the same dead mirror) are dropped:
        // the line carries no locale, so a second copy adds noise rather than evidence.
        $carried = array_values(array_diff($feed->warnings, $fallback->warnings));

        return $fallback->withWarningsPrepended($carried);
    }

    /**
     * One locale, walked in mirror order (mirrors.md §4): the first mirror whose walk fully verifies
     * wins; transport failures and verification rejects alike move to the next base, each surfaced
     * as a warning. The whole walk — `current` → `feed-targets` → **feed body** — runs against a
     * single store, so a body is never paired with another mirror's targets (§4.1).
     *
     * Returns the resulting feed **and every attempt's reason**, because §4.3 forbids deciding
     * anything from the last failure alone — the caller needs the whole set.
     *
     * @return array{feed: OriginFeed, reasons: list<string>}
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
    ): array {
        $mirrorWarnings = [];
        $reasons = [];
        $last = null;

        foreach ($stores->stores() as $baseUrl => $store) {
            $feed = $this->readLocaleFrom($query, $locale, $store, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);

            if ($feed->available) {
                return ['feed' => $feed->withWarningsPrepended($mirrorWarnings), 'reasons' => $reasons];
            }

            $reason = $feed->reason ?? 'unknown';
            $mirrorWarnings[] = sprintf('mirror failover: %s skipped (%s)', $baseUrl, $reason);
            $reasons[] = $reason;
            $last = $feed;
        }

        $last ??= OriginFeed::unavailable($query, $locale, 'origin_not_configured');

        return ['feed' => $last->withWarningsPrepended($mirrorWarnings), 'reasons' => $reasons];
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
        // The coordinate is per (product, audience, locale) and carries `$locale` — the locale being
        // walked, not `$query->locale`. On the missing-locale fallback the en cycle must compare
        // against en's own counter, and advance that one; reusing the requested locale's would be the
        // same cross-coordinate mistake #424 fixed, one level down.
        $coordinate = OriginGenWatermarkCoordinate::forFeed($query->product, $query->audience, $locale);
        $persistedGen = $watermarks->current($coordinate) ?? $genFloor;
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
            // A published-empty feed is a fully verified generation, so it advances the watermark
            // like any other — otherwise publishing an empty feed would silently lower the floor.
            $this->advance($watermarks, $coordinate, $outcome->gen, $now);

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

        $this->advance($watermarks, $coordinate, $outcome->gen, $now);

        return OriginFeed::available($query, $locale, $count, $items, $outcome->freshness, $outcome->warnings);
    }

    /**
     * ADR 0017 §5 anti-rollback for the feed tree (#429 — the feed half of #411). Advanced only on
     * the paths that actually deliver a feed, which is stricter than "the `current` walk verified":
     * a mirror whose body fails its `content_sha256` or decodes to garbage returns unavailable and
     * must not be able to move the floor. The store is monotonic, so the steady state — polling an
     * unchanged feed — is a no-op write.
     *
     * `$coordinate` is the walked locale's, so the en fallback advances en's counter and leaves the
     * requested locale's untouched.
     */
    private function advance(
        OriginGenWatermarkRepositoryInterface $watermarks,
        OriginGenWatermarkCoordinate $coordinate,
        ?int $gen,
        DateTimeImmutable $now,
    ): void {
        if ($gen === null) {
            return;
        }

        $watermarks->record(
            $coordinate,
            $gen,
            $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        );
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
