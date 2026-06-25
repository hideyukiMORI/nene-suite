<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\InstalledApp;
use NeNeSuite\InstalledApps\ListInstalledAppsOutput;
use NeNeSuite\InstalledApps\SsotRole;
use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\GetOriginUpdatesUseCase;
use NeNeSuite\Origin\OriginClientConfig;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchorProvider;
use NeNeSuite\Origin\OriginUpdateAggregator;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdateStatus;
use NeNeSuite\SiblingHealth\InstalledVersionResolver;
use NeNeSuite\Tests\SiblingHealth\FakeSiblingHealthClient;
use NeNeSuite\Tests\SiblingHealth\InMemoryInstalledVersionRepository;
use PHPUnit\Framework\TestCase;

final class GetOriginUpdatesUseCaseTest extends TestCase
{
    private const string CORPUS = __DIR__ . '/../fixtures/origin-conformance';

    private const string NOW = '2026-06-20T00:00:00Z';

    public function testDisabledWhenNotConfigured(): void
    {
        $useCase = $this->useCase(new OriginClientConfig('', 10, 1, 1, null), null);

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        self::assertFalse($output->available);
        self::assertSame([], $output->updates);
    }

    public function testDisabledWhenUrlSetButNoTrustAnchor(): void
    {
        $useCase = $this->useCase(new OriginClientConfig('https://origin.example.com', 10, 1, 1, null), null);

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        self::assertFalse($output->available);
    }

    public function testEnabledReturnsVerifiedSignals(): void
    {
        $anchorPath = self::CORPUS . '/trust-anchor.json';
        $useCase = $this->useCase(
            new OriginClientConfig('https://origin.example.com', 10, 1, 1, $anchorPath),
            $anchorPath,
        );

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        self::assertTrue($output->available);
        self::assertCount(1, $output->updates);

        $signal = $output->updates[0] ?? null;
        self::assertInstanceOf(OriginUpdateSignal::class, $signal);
        self::assertSame('nene-invoice', $signal->product);
        self::assertSame('stable', $signal->channel);
        self::assertNull($signal->installedVersion);
        self::assertSame(OriginUpdateStatus::Unknown, $signal->status);
        self::assertSame('1.4.0', $signal->latestVersion);
    }

    public function testEnabledFlipsStatusWhenInstalledVersionIsKnown(): void
    {
        $anchorPath = self::CORPUS . '/trust-anchor.json';
        $useCase = $this->useCase(
            new OriginClientConfig('https://origin.example.com', 10, 1, 1, $anchorPath),
            $anchorPath,
            ['https://example.com/nene-invoice/' => '1.3.0'],
        );

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        $signal = $output->updates[0] ?? null;
        self::assertInstanceOf(OriginUpdateSignal::class, $signal);
        // Installed 1.3.0 < latest 1.4.0 (and >= min_supported 1.2.0): the diff is now a real
        // update_available instead of unknown.
        self::assertSame('1.3.0', $signal->installedVersion);
        self::assertSame(OriginUpdateStatus::UpdateAvailable, $signal->status);
        self::assertSame('1.4.0', $signal->latestVersion);
    }

    /**
     * @param array<string, ?string> $versionsByUrl
     */
    private function useCase(OriginClientConfig $config, ?string $anchorPath, array $versionsByUrl = []): GetOriginUpdatesUseCase
    {
        return new GetOriginUpdatesUseCase(
            $config,
            new OriginTrustAnchorProvider($anchorPath),
            new FakeListInstalledAppsUseCase(new ListInstalledAppsOutput([
                new InstalledApp('nene-invoice', 'NeNe Invoice', 'https://example.com/nene-invoice/', null, SsotRole::None),
            ])),
            new InstalledVersionResolver(new FakeSiblingHealthClient($versionsByUrl), new InMemoryInstalledVersionRepository()),
            new FilesystemOriginObjectStore(self::CORPUS . '/cases/valid-update-reduced'),
            new OriginUpdateAggregator(new OriginReadModelVerifier()),
            new InMemoryOriginGenWatermarkRepository(),
        );
    }
}
