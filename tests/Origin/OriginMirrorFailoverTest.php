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

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir() . '/nene-suite-mirror-' . bin2hex(random_bytes(6));
        mkdir($this->workspace, 0o777, true);
    }

    protected function tearDown(): void
    {
        self::removeTree($this->workspace);

        parent::tearDown();
    }

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
     * §4.3 order-independence. The locale fallback must be decided from the **set** of attempt
     * results, not from whichever base happened to fail last. Mirrors serve byte-identical objects
     * (§5), so a missing `ja` variant is missing everywhere — one degraded mirror denying the walk
     * next to another's honest 404 must not suppress the fallback.
     *
     * Both orders below describe the same reality (variant absent + one broken mirror), so both
     * must reach the same decision. Reading only the last reason splits them: before the fix the
     * order ending on the denial stayed on `ja` and never tried `en`.
     */
    public function testLocaleFallbackDoesNotDependOnMirrorOrder(): void
    {
        $degradedFirst = $this->feed([
            'https://nene-origin.dev' => $this->degradedMirror(),
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
        ]);

        $unreachableFirst = $this->feed([
            'https://nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
            'https://m2.nene-origin.dev' => $this->degradedMirror(),
        ]);

        self::assertSame('en', $degradedFirst->servedLocale);
        self::assertSame('en', $unreachableFirst->servedLocale);
        self::assertSame($degradedFirst->available, $unreachableFirst->available);
    }

    public function testLocaleFallbackCarriesTheDenialEvidenceForward(): void
    {
        $feed = $this->feed([
            'https://nene-origin.dev' => $this->degradedMirror(),
            'https://m2.nene-origin.dev' => self::CORPUS . '/cases/no-such-mirror',
        ]);

        // The fallback is the path that can hide a degraded mirror behind a working result, so the
        // denial must survive into the returned feed rather than being dropped with the ja attempt.
        self::assertContains(
            'mirror failover: https://nene-origin.dev skipped (content_hash_mismatch)',
            $feed->warnings,
        );
    }

    public function testDenialWithoutAnyMissingVariantDoesNotFallBack(): void
    {
        // "A verification failure does not fall back" — no attempt reported the variant absent, so
        // there is nothing to fall back *from*. Order must not change that either.
        foreach ([['a', 'b'], ['b', 'a']] as [$first, $second]) {
            $feed = $this->feed([
                'https://' . $first . '.nene-origin.dev' => $this->degradedMirror($first),
                'https://' . $second . '.nene-origin.dev' => $this->degradedMirror($second),
            ]);

            self::assertFalse($feed->available);
            self::assertSame('ja', $feed->servedLocale, 'a denial-only outcome must not trigger the en fallback');
        }
    }

    /**
     * A mirror that serves the `ja` feed but whose body no longer hashes to the value its verified
     * targets leaf names — a denial that is emphatically **not** "variant absent". The corpus has no
     * such case (every `neg-*` case lacks the feeds path, so it 404s and reads as unreachable), so
     * it is built here by copying the valid case and corrupting only the body.
     */
    private function degradedMirror(string $suffix = ''): string
    {
        $directory = $this->workspace . '/degraded' . $suffix;

        if (!is_dir($directory)) {
            self::copyTree(self::CORPUS . '/cases/valid-feed', $directory);

            foreach (glob($directory . '/v1/feed-bodies/*.json') ?: [] as $body) {
                file_put_contents($body, '[{"id":"tampered"}]');
            }
        }

        return $directory;
    }

    private static function copyTree(string $from, string $to): void
    {
        mkdir($to, 0o777, true);

        foreach (scandir($from) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $source = $from . '/' . $entry;
            $target = $to . '/' . $entry;

            if (is_dir($source)) {
                self::copyTree($source, $target);
            } else {
                copy($source, $target);
            }
        }
    }

    private static function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path . '/' . $entry;
            is_dir($child) ? self::removeTree($child) : unlink($child);
        }

        rmdir($path);
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
