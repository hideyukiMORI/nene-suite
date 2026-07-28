<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\OriginFeed;
use NeNeSuite\Origin\OriginFeedKind;
use NeNeSuite\Origin\OriginFeedQuery;
use NeNeSuite\Origin\OriginFeedReader;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchor;
use NeNeSuite\Origin\OriginUpdateAggregator;
use NeNeSuite\Origin\OriginUpdateQuery;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdateStatus;
use PHPUnit\Framework\TestCase;

/**
 * Ordered mirror failover (`nene-origin/docs/spec/mirrors.md` §4) over the conformance corpus: each
 * "mirror" is a case directory, an absent directory models an unreachable one. The rules under test
 * are §4.2 (a verification REJECT fails the attempt exactly like a transport error) and §4.1 (a walk
 * completes against a single base — the accepted data comes from the mirror that verified).
 */
final class OriginMirrorFailoverTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';

    private const string NOW = '2026-06-20T00:00:00Z';

    public function testFailsOverToTheSecondMirrorWhenTheFirstIsUnreachable(): void
    {
        $signal = $this->signal([
            'https://nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/valid-update-reduced',
        ], '1.3.0');

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        self::assertSame('1.4.0', $signal->latestVersion);
        self::assertContains(
            'mirror failover: https://nene-origin.dev skipped (origin_unreachable)',
            $signal->warnings,
        );
    }

    public function testAVerificationRejectIsADenialThatMovesToTheNextMirror(): void
    {
        // §4.2: "an attempt fails on transport errors and equally on verification REJECT".
        $signal = $this->signal([
            'https://nene-origin.dev' => self::CORPUS . '/cases/neg-disallowed-alg',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/valid-update-reduced',
        ], '1.3.0');

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        self::assertNull($signal->reason);
        self::assertContains(
            'mirror failover: https://nene-origin.dev skipped (alg_not_allowed)',
            $signal->warnings,
        );
    }

    public function testTheCycleFailsWhenEveryMirrorFailsAndKeepsTheLastReason(): void
    {
        $signal = $this->signal([
            'https://nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/neg-disallowed-alg',
        ], '1.3.0');

        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('alg_not_allowed', $signal->reason);
        self::assertSame([
            'mirror failover: https://nene-origin.dev skipped (origin_unreachable)',
            'mirror failover: https://m2.nene-origin.dev skipped (alg_not_allowed)',
        ], $signal->warnings);
    }

    public function testAVerifiedPrimaryStopsTheWalkAndNeverTouchesTheSecondary(): void
    {
        $provider = new FakeMirrorObjectStoreProvider([
            'https://nene-origin.dev' => self::CORPUS . '/cases/valid-update-reduced',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
        ]);

        $signal = $this->aggregate($provider, '1.3.0');

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        self::assertSame([], $signal->warnings);
        self::assertSame(['https://nene-origin.dev'], $provider->requested);
    }

    public function testAnEmptyMirrorListIsNotConfiguredRatherThanUnreachable(): void
    {
        $signal = $this->aggregate(new FakeMirrorObjectStoreProvider([]), '1.3.0');

        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('origin_not_configured', $signal->reason);
    }

    public function testFeedsFailOverAcrossMirrorsToo(): void
    {
        $feed = $this->feed([
            'https://nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/valid-feed',
        ]);

        self::assertTrue($feed->available);
        self::assertContains(
            'mirror failover: https://nene-origin.dev skipped (origin_unreachable)',
            $feed->warnings,
        );
    }

    public function testFeedsSurfaceEveryFailedMirrorWhenNoneVerifies(): void
    {
        $feed = $this->feed([
            'https://nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror-either',
        ]);

        self::assertFalse($feed->available);
        self::assertSame('origin_unreachable', $feed->reason);
        // The en fallback is a fresh cycle: what surfaces is that cycle's own per-mirror warnings
        // (the requested-locale attempt failed for the same two mirrors, so repeating them adds
        // nothing but noise).
        self::assertSame('en', $feed->servedLocale);
        self::assertSame([
            'mirror failover: https://nene-origin.dev skipped (origin_unreachable)',
            'mirror failover: https://m2.nene-origin.dev skipped (origin_unreachable)',
        ], $feed->warnings);
    }

    /**
     * @param array<string, string> $mirrors
     */
    private function signal(array $mirrors, string $installed): OriginUpdateSignal
    {
        return $this->aggregate(new FakeMirrorObjectStoreProvider($mirrors), $installed);
    }

    private function aggregate(FakeMirrorObjectStoreProvider $provider, string $installed): OriginUpdateSignal
    {
        $signals = (new OriginUpdateAggregator(new OriginReadModelVerifier()))->aggregate(
            [new OriginUpdateQuery('nene-invoice', 'stable', $installed)],
            $provider,
            $this->anchor(),
            1,
            1,
            new DateTimeImmutable(self::NOW),
            new InMemoryOriginGenWatermarkRepository(),
        );

        $signal = $signals[0] ?? null;
        self::assertInstanceOf(OriginUpdateSignal::class, $signal);

        return $signal;
    }

    /**
     * @param array<string, string> $mirrors
     */
    private function feed(array $mirrors): OriginFeed
    {
        return (new OriginFeedReader(new OriginReadModelVerifier()))->read(
            new OriginFeedQuery('nene-invoice', 'free', 'ja', OriginFeedKind::Announcement),
            new FakeMirrorObjectStoreProvider($mirrors),
            $this->anchor(),
            1,
            1,
            new DateTimeImmutable(self::NOW),
            new InMemoryOriginGenWatermarkRepository(),
        );
    }

    private function anchor(): OriginTrustAnchor
    {
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        return OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []);
    }
}
