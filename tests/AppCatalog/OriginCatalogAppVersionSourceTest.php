<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use DateTimeImmutable;
use NeNeSuite\AppCatalog\OriginCatalogAppVersionSource;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdatesOutput;
use NeNeSuite\Origin\OriginUpdateStatus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OriginCatalogAppVersionSourceTest extends TestCase
{
    private const string NOW = '2026-06-25T00:00:00Z';

    public function testEmptyWhenOriginDisabled(): void
    {
        $source = new OriginCatalogAppVersionSource(new FakeGetOriginUpdatesUseCase(OriginUpdatesOutput::disabled()));

        self::assertSame([], $source->versions(new DateTimeImmutable(self::NOW)));
    }

    public function testMirrorsInstalledAndAvailableFromSignals(): void
    {
        $output = OriginUpdatesOutput::enabled([
            new OriginUpdateSignal('nene-invoice', 'stable', '1.3.0', OriginUpdateStatus::UpdateAvailable, '1.4.0'),
        ]);
        $source = new OriginCatalogAppVersionSource(new FakeGetOriginUpdatesUseCase($output));

        $versions = $source->versions(new DateTimeImmutable(self::NOW));

        self::assertArrayHasKey('nene-invoice', $versions);
        self::assertSame('1.3.0', $versions['nene-invoice']->installedVersion);
        self::assertSame('1.4.0', $versions['nene-invoice']->availableVersion);
    }

    public function testDegradesToEmptyOnFailure(): void
    {
        $source = new OriginCatalogAppVersionSource(
            new FakeGetOriginUpdatesUseCase(null, new RuntimeException('boom')),
        );

        self::assertSame([], $source->versions(new DateTimeImmutable(self::NOW)));
    }
}
