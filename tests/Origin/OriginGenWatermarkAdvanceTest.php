<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginFeedKind;
use NeNeSuite\Origin\OriginFeedQuery;
use NeNeSuite\Origin\OriginFeedReader;
use NeNeSuite\Origin\OriginGenWatermarkCoordinate;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchor;
use NeNeSuite\Origin\OriginUpdateAggregator;
use NeNeSuite\Origin\OriginUpdateQuery;
use NeNeSuite\Origin\OriginUpdateStatus;
use NeNeSuite\Origin\SingleOriginObjectStoreProvider;
use PHPUnit\Framework\TestCase;

/**
 * Anti-rollback watermark round trip (ADR 0017 §5 / #411). The O2 store landed with no production
 * caller for `record()`, so the watermark never advanced and `persisted_gen` fell back to the
 * build-time floor on every poll — the rollback guard was reduced to "not below the build floor".
 *
 * These cases close the loop end to end: an accepted walk **writes** the generation, and a written
 * watermark **refuses** an older one. The corpus tree `valid-update-reduced` is `gen = 42` with
 * `min_valid_generation = 30`.
 *
 * Why the replay case seeds the store instead of serving a re-signed older tree: the conformance
 * corpus carries no second, older, validly-signed generation for a product, and the corpus is pinned
 * (objects cannot be edited without breaking their detached JWS). Seeding is the faithful stand-in —
 * it reproduces exactly the state the first test proves the client now reaches on its own.
 */
final class OriginGenWatermarkAdvanceTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';
    private const int CORPUS_GEN = 42;

    public function testAnAcceptedWalkAdvancesTheWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        self::assertNull($watermarks->current(self::updateCoordinate()), 'precondition: nothing persisted yet');

        $signal = $this->aggregate('valid-update-reduced', $watermarks);

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        // The regression #411 records: before the fix this stayed null even though the tree verified.
        self::assertSame(self::CORPUS_GEN, $watermarks->current(self::updateCoordinate()));
    }

    public function testARejectedWalkNeverAdvancesTheWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        $signal = $this->aggregate('neg-disallowed-alg', $watermarks);

        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('alg_not_allowed', $signal->reason);
        // A mirror that fails verification must not be able to move the anti-rollback floor.
        self::assertNull($watermarks->current(self::updateCoordinate()));
    }

    public function testReplayingAnOlderGenerationIsRefusedOnceTheWatermarkHasAdvanced(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();
        // Stand-in for "this client already accepted gen 43" — the state the first test proves an
        // accepted walk now reaches by itself.
        $watermarks->record(self::updateCoordinate(), self::CORPUS_GEN + 1, '2026-06-20T00:00:00Z');

        $signal = $this->aggregate('valid-update-reduced', $watermarks);

        // The replayed tree is validly signed and inside its freshness window; only the persisted
        // watermark can refuse it. `min_valid_generation` cannot: the yank check compares the served
        // object's own `gen` against the `min_valid_generation` carried by that same object, so a
        // replay of an older signed `current` brings its own (lower) watermark along and passes.
        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('rollback', $signal->reason);
        self::assertNull($signal->latestVersion);
        // Refusing must not regress what was already persisted.
        self::assertSame(self::CORPUS_GEN + 1, $watermarks->current(self::updateCoordinate()));
    }

    public function testRepeatedPollsOfTheSameGenerationStayAccepted(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        $first = $this->aggregate('valid-update-reduced', $watermarks);
        $second = $this->aggregate('valid-update-reduced', $watermarks);

        // The steady state — one poll per `poll_after` against an unchanged tree. Advancing to the
        // generation just accepted must not lock the client out of that same generation next poll.
        self::assertSame(OriginUpdateStatus::UpdateAvailable, $first->status);
        self::assertSame(OriginUpdateStatus::UpdateAvailable, $second->status);
        self::assertSame(self::CORPUS_GEN, $watermarks->current(self::updateCoordinate()));
    }

    public function testAnAcceptedFeedWalkDoesNotTouchTheUpdateWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        $feed = $this->readFeed('valid-feed', 'ja', $watermarks);

        self::assertTrue($feed->available, 'precondition: the feed tree verifies');
        // Origin (spec owner, 2026-08-05): `gen` is counted per tree coordinate, not per product,
        // so the sequences are coprime — this corpus has the same product at update gen 42 and feed
        // gen 7. A feed accept must never land on the update counter.
        //
        // This assertion predates #424, where it was justified as "coordinates are independent, so
        // feeds must not record at all". That inference was wrong in a way worth keeping visible:
        // the premise argued for **separate keys**, not for dropping the write. Reading it as a ban
        // on writing is what left the shared product key in place, and the shared key is what made
        // an accepted update lock out every feed. Feeds still do not record — that is now a tracked
        // gap (the feed half of #411), not a ruling.
        self::assertNull($watermarks->current(self::updateCoordinate()));
    }

    /**
     * 🔴 The wiring #424 is about, and the one thing the conformance corpus structurally cannot
     * catch: the corpus injects `client` state per case, so it never crosses the repository that
     * joins an update walk to a feed walk. 19/19 PASS while this scenario fails is exactly what
     * production did.
     *
     * Before the coordinate split, the update accept below advanced the one shared row to gen 42,
     * and each feed read that followed compared its own gen (ja 7, en 3) against that 42 and
     * returned `rollback` — measured, both locales, including the `en` fallback target.
     */
    public function testAFeedStaysReadableAfterAnUpdateWalkAdvancedTheWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        $signal = $this->aggregate('valid-update-reduced', $watermarks);

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status, 'precondition: the update walk is accepted');
        self::assertSame(self::CORPUS_GEN, $watermarks->current(self::updateCoordinate()), 'precondition: the update watermark advanced past every feed gen');

        $ja = $this->readFeed('valid-feed', 'ja', $watermarks);
        $en = $this->readFeed('valid-feed-en', 'en', $watermarks);

        self::assertTrue($ja->available, 'the ja feed must survive an update walk on the same product');
        self::assertTrue($en->available, 'the en fallback target must survive it too');
        self::assertNotSame('rollback', $ja->reason);
        self::assertNotSame('rollback', $en->reason);
    }

    private function readFeed(string $case, string $locale, InMemoryOriginGenWatermarkRepository $watermarks): \NeNeSuite\Origin\OriginFeed
    {
        return (new OriginFeedReader(new OriginReadModelVerifier()))->read(
            new OriginFeedQuery('nene-invoice', 'free', $locale, OriginFeedKind::Announcement),
            new SingleOriginObjectStoreProvider(new FilesystemOriginObjectStore(self::CORPUS . '/cases/' . $case)),
            self::anchor(),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            $watermarks,
        );
    }

    private function aggregate(string $case, InMemoryOriginGenWatermarkRepository $watermarks): \NeNeSuite\Origin\OriginUpdateSignal
    {
        $signals = (new OriginUpdateAggregator(new OriginReadModelVerifier()))->aggregate(
            [new OriginUpdateQuery('nene-invoice', 'stable', '1.3.0')],
            new SingleOriginObjectStoreProvider(new FilesystemOriginObjectStore(self::CORPUS . '/cases/' . $case)),
            self::anchor(),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            $watermarks,
        );

        $signal = $signals[0] ?? null;
        self::assertInstanceOf(\NeNeSuite\Origin\OriginUpdateSignal::class, $signal);

        return $signal;
    }

    private static function anchor(): OriginTrustAnchor
    {
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        return OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []);
    }

    private static function updateCoordinate(): OriginGenWatermarkCoordinate
    {
        return OriginGenWatermarkCoordinate::forUpdate('nene-invoice');
    }
}
