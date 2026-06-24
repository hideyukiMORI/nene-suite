<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginFeed;
use NeNeSuite\Origin\OriginFeedKind;
use NeNeSuite\Origin\OriginFeedQuery;
use NeNeSuite\Origin\OriginFeedReader;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchor;
use PHPUnit\Framework\TestCase;

/**
 * Feed reading over the vendored corpus `valid-feed` (nene-invoice/free/ja/announcement, count 2).
 * Exercises the happy path, the en locale fallback, and a non-delegated kind.
 */
final class OriginFeedReaderTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';

    public function testReadsAvailableAnnouncementFeed(): void
    {
        $feed = $this->read(new OriginFeedQuery('nene-invoice', 'free', 'ja', OriginFeedKind::Announcement));

        self::assertTrue($feed->available);
        self::assertSame('ja', $feed->servedLocale);
        self::assertSame(2, $feed->count);
        self::assertCount(2, $feed->items);
        self::assertNull($feed->reason);

        $first = $feed->items[0] ?? null;
        self::assertIsArray($first);
        self::assertSame('2026-06-19-tier-a-updates', $first['id'] ?? null);
    }

    public function testFallsBackToEnWhenLocaleMissing(): void
    {
        // fr is unpublished → fall back to en; en is also absent in this corpus case → unavailable at en.
        $feed = $this->read(new OriginFeedQuery('nene-invoice', 'free', 'fr', OriginFeedKind::Announcement));

        self::assertFalse($feed->available);
        self::assertSame('fr', $feed->requestedLocale);
        self::assertSame('en', $feed->servedLocale);
        self::assertSame('origin_unreachable', $feed->reason);
    }

    public function testUnavailableWhenKindNotDelegated(): void
    {
        // The locale's snapshot delegates only the announcement leaf, not an ad leaf.
        $feed = $this->read(new OriginFeedQuery('nene-invoice', 'free', 'ja', OriginFeedKind::Ad));

        self::assertFalse($feed->available);
        self::assertSame('targets_hash_mismatch', $feed->reason);
    }

    private function read(OriginFeedQuery $query): OriginFeed
    {
        return (new OriginFeedReader(new OriginReadModelVerifier()))->read(
            $query,
            new FilesystemOriginObjectStore(self::CORPUS . '/cases/valid-feed'),
            $this->anchor(),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            new InMemoryOriginGenWatermarkRepository(),
        );
    }

    private function anchor(): OriginTrustAnchor
    {
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        return OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []);
    }
}
