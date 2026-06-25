<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\InstalledApp;
use NeNeSuite\InstalledApps\SsotRole;
use NeNeSuite\SiblingHealth\InstalledVersionResolver;
use PHPUnit\Framework\TestCase;

final class InstalledVersionResolverTest extends TestCase
{
    private const string NOW = '2026-06-25T00:00:00Z';

    private const string URL = 'https://example.com/nene-invoice/';

    public function testProbedVersionIsUsedAndRecorded(): void
    {
        $repository = new InMemoryInstalledVersionRepository();
        $resolver = new InstalledVersionResolver(
            new FakeSiblingHealthClient([self::URL => '1.3.0']),
            $repository,
        );

        $result = $resolver->resolve([$this->app()], new DateTimeImmutable(self::NOW));

        self::assertSame(['nene-invoice' => '1.3.0'], $result);
        self::assertSame('1.3.0', $repository->current('nene-invoice'));
    }

    public function testFallsBackToStoredVersionWhenProbeFails(): void
    {
        $repository = new InMemoryInstalledVersionRepository();
        $repository->record('nene-invoice', '1.2.0', self::NOW);
        $resolver = new InstalledVersionResolver(new FakeSiblingHealthClient([]), $repository);

        $result = $resolver->resolve([$this->app()], new DateTimeImmutable(self::NOW));

        self::assertSame(['nene-invoice' => '1.2.0'], $result);
    }

    public function testUnknownWhenNeverProbedAndNothingStored(): void
    {
        $resolver = new InstalledVersionResolver(
            new FakeSiblingHealthClient([]),
            new InMemoryInstalledVersionRepository(),
        );

        $result = $resolver->resolve([$this->app()], new DateTimeImmutable(self::NOW));

        self::assertSame(['nene-invoice' => null], $result);
    }

    private function app(): InstalledApp
    {
        return new InstalledApp('nene-invoice', 'NeNe Invoice', self::URL, null, SsotRole::None);
    }
}
