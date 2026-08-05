<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginFeedKind;
use NeNeSuite\Origin\OriginFeedQuery;
use NeNeSuite\Origin\OriginFeedReader;
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

        self::assertNull($watermarks->current('nene-invoice'), 'precondition: nothing persisted yet');

        $signal = $this->aggregate('valid-update-reduced', $watermarks);

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        // The regression #411 records: before the fix this stayed null even though the tree verified.
        self::assertSame(self::CORPUS_GEN, $watermarks->current('nene-invoice'));
    }

    public function testARejectedWalkNeverAdvancesTheWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();

        $signal = $this->aggregate('neg-disallowed-alg', $watermarks);

        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('alg_not_allowed', $signal->reason);
        // A mirror that fails verification must not be able to move the anti-rollback floor.
        self::assertNull($watermarks->current('nene-invoice'));
    }

    public function testReplayingAnOlderGenerationIsRefusedOnceTheWatermarkHasAdvanced(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();
        // Stand-in for "this client already accepted gen 43" — the state the first test proves an
        // accepted walk now reaches by itself.
        $watermarks->record('nene-invoice', self::CORPUS_GEN + 1, '2026-06-20T00:00:00Z');

        $signal = $this->aggregate('valid-update-reduced', $watermarks);

        // The replayed tree is validly signed and inside its freshness window; only the persisted
        // watermark can refuse it. `min_valid_generation` cannot: the yank check compares the served
        // object's own `gen` against the `min_valid_generation` carried by that same object, so a
        // replay of an older signed `current` brings its own (lower) watermark along and passes.
        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('rollback', $signal->reason);
        self::assertNull($signal->latestVersion);
        // Refusing must not regress what was already persisted.
        self::assertSame(self::CORPUS_GEN + 1, $watermarks->current('nene-invoice'));
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
        self::assertSame(self::CORPUS_GEN, $watermarks->current('nene-invoice'));
    }

    public function testAnAcceptedFeedWalkDoesNotTouchTheUpdateWatermark(): void
    {
        $watermarks = new InMemoryOriginGenWatermarkRepository();
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        $feed = (new OriginFeedReader(new OriginReadModelVerifier()))->read(
            new OriginFeedQuery('nene-invoice', 'free', 'ja', OriginFeedKind::Announcement),
            new SingleOriginObjectStoreProvider(new FilesystemOriginObjectStore(self::CORPUS . '/cases/valid-feed')),
            OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            $watermarks,
        );

        self::assertTrue($feed->available, 'precondition: the feed tree verifies');
        // Origin (spec owner, 2026-08-05): `gen` is counted per tree coordinate, not per product —
        // update is keyed `{product}/{channel}`, feed `feeds/{product}/{audience}/{locale}`, so the
        // sequences are coprime. This corpus proves it: the same product is update gen 42 and feed
        // gen 7. Recording a feed accept into this product-scoped watermark would therefore refuse
        // the update tree (7 < 42) — or, once the update tree advanced it, refuse every feed.
        // verification-order §3 says "for this tree" and §7 persists only after a successful apply,
        // which feeds never do. This assertion pins that ruling so it cannot be "fixed" back.
        self::assertNull($watermarks->current('nene-invoice'));
    }

    private function aggregate(string $case, InMemoryOriginGenWatermarkRepository $watermarks): \NeNeSuite\Origin\OriginUpdateSignal
    {
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        $signals = (new OriginUpdateAggregator(new OriginReadModelVerifier()))->aggregate(
            [new OriginUpdateQuery('nene-invoice', 'stable', '1.3.0')],
            new SingleOriginObjectStoreProvider(new FilesystemOriginObjectStore(self::CORPUS . '/cases/' . $case)),
            OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            $watermarks,
        );

        $signal = $signals[0] ?? null;
        self::assertInstanceOf(\NeNeSuite\Origin\OriginUpdateSignal::class, $signal);

        return $signal;
    }
}
