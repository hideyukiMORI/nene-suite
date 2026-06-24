<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\InstalledApp;
use NeNeSuite\InstalledApps\ListInstalledAppsOutput;
use NeNeSuite\InstalledApps\SsotRole;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\GetOriginFeedsUseCase;
use NeNeSuite\Origin\OriginClientConfig;
use NeNeSuite\Origin\OriginFeed;
use NeNeSuite\Origin\OriginFeedKind;
use NeNeSuite\Origin\OriginFeedReader;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchorProvider;
use PHPUnit\Framework\TestCase;

final class GetOriginFeedsUseCaseTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';

    private const string NOW = '2026-06-20T00:00:00Z';

    public function testDisabledWhenNotConfigured(): void
    {
        $output = $this->useCase(new OriginClientConfig('', 10, 1, 1, null), null)
            ->execute(OriginFeedKind::Announcement, 'ja', new DateTimeImmutable(self::NOW));

        self::assertFalse($output->available);
        self::assertSame([], $output->feeds);
    }

    public function testEnabledReturnsAnnouncementFeed(): void
    {
        $feed = $this->firstFeed(OriginFeedKind::Announcement);

        self::assertTrue($feed->available);
        self::assertSame('nene-invoice', $feed->product);
        self::assertSame('free', $feed->audience);
        self::assertSame('ja', $feed->servedLocale);
        self::assertSame(2, $feed->count);
        self::assertCount(2, $feed->items);
    }

    public function testHouseAdsUnavailableWhenKindNotDelegated(): void
    {
        $feed = $this->firstFeed(OriginFeedKind::Ad);

        self::assertFalse($feed->available);
        self::assertSame('targets_hash_mismatch', $feed->reason);
    }

    private function firstFeed(OriginFeedKind $kind): OriginFeed
    {
        $anchorPath = self::CORPUS . '/trust-anchor.json';
        $output = $this->useCase(new OriginClientConfig('https://origin.example.com', 10, 1, 1, $anchorPath), $anchorPath)
            ->execute($kind, 'ja', new DateTimeImmutable(self::NOW));

        self::assertTrue($output->available);
        self::assertCount(1, $output->feeds);

        $feed = $output->feeds[0] ?? null;
        self::assertInstanceOf(OriginFeed::class, $feed);

        return $feed;
    }

    private function useCase(OriginClientConfig $config, ?string $anchorPath): GetOriginFeedsUseCase
    {
        return new GetOriginFeedsUseCase(
            $config,
            new OriginTrustAnchorProvider($anchorPath),
            new FakeListInstalledAppsUseCase(new ListInstalledAppsOutput([
                new InstalledApp('nene-invoice', 'NeNe Invoice', 'https://example.com/nene-invoice/', null, SsotRole::None),
            ])),
            new FilesystemOriginObjectStore(self::CORPUS . '/cases/valid-feed'),
            new OriginFeedReader(new OriginReadModelVerifier()),
            new InMemoryOriginGenWatermarkRepository(),
        );
    }
}
