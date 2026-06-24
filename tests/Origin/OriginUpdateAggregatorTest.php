<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchor;
use NeNeSuite\Origin\OriginUpdateAggregator;
use NeNeSuite\Origin\OriginUpdateQuery;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdateStatus;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end aggregation over the vendored conformance corpus: the verified `valid-update-reduced`
 * tree (nene-invoice/stable, latest 1.4.0, min_supported 1.2.0) drives the version comparison, and a
 * rejecting tree degrades to `unavailable`.
 */
final class OriginUpdateAggregatorTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';

    public function testUpdateAvailable(): void
    {
        $signal = $this->signal('valid-update-reduced', '1.3.0');

        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        self::assertSame('1.4.0', $signal->latestVersion);
        self::assertSame('1.2.0', $signal->minSupportedVersion);
        self::assertNotNull($signal->changelogUrl);
        self::assertSame('2026-06-19T00:00:00Z', $signal->releasedAt);
        self::assertNull($signal->reason);
    }

    public function testForcedWhenBelowSecurityFloor(): void
    {
        $signal = $this->signal('valid-update-reduced', '1.1.0');

        self::assertSame(OriginUpdateStatus::Forced, $signal->status);
    }

    public function testUpToDateWhenAtLatest(): void
    {
        $signal = $this->signal('valid-update-reduced', '1.4.0');

        self::assertSame(OriginUpdateStatus::UpToDate, $signal->status);
    }

    public function testUnavailableOnVerificationFailure(): void
    {
        $signal = $this->signal('neg-disallowed-alg', '1.3.0');

        self::assertSame(OriginUpdateStatus::Unavailable, $signal->status);
        self::assertSame('alg_not_allowed', $signal->reason);
        self::assertNull($signal->latestVersion);
    }

    private function signal(string $case, string $installed): OriginUpdateSignal
    {
        $signals = (new OriginUpdateAggregator(new OriginReadModelVerifier()))->aggregate(
            [new OriginUpdateQuery('nene-invoice', 'stable', $installed)],
            new FilesystemOriginObjectStore(self::CORPUS . '/cases/' . $case),
            $this->anchor(),
            1,
            1,
            new DateTimeImmutable('2026-06-20T00:00:00Z'),
            new InMemoryOriginGenWatermarkRepository(),
        );

        $signal = $signals[0] ?? null;
        self::assertInstanceOf(OriginUpdateSignal::class, $signal);

        return $signal;
    }

    private function anchor(): OriginTrustAnchor
    {
        $decoded = json_decode((string) file_get_contents(self::CORPUS . '/trust-anchor.json'), true, flags: JSON_THROW_ON_ERROR);

        return OriginTrustAnchor::fromArray(is_array($decoded) ? $decoded : []);
    }
}
