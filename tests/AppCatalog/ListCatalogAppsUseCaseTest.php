<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use DateTimeImmutable;
use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\AppCatalog\CatalogAppVersions;
use NeNeSuite\AppCatalog\CatalogReadException;
use NeNeSuite\AppCatalog\ListCatalogAppsUseCase;
use PHPUnit\Framework\TestCase;

final class ListCatalogAppsUseCaseTest extends TestCase
{
    private const string NOW = '2026-06-25T00:00:00Z';

    public function testReturnsCatalogVersionAppsAndMirroredVersions(): void
    {
        $useCase = new ListCatalogAppsUseCase(
            new InMemoryCatalogAppRepository(new Catalog(1, [$this->invoice(), $this->clear()])),
            new FakeCatalogAppVersionSource([
                'nene-invoice' => new CatalogAppVersions('1.3.0', '1.4.0'),
            ]),
        );

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        self::assertSame(1, $output->version);
        self::assertCount(2, $output->apps);
        self::assertSame('nene-invoice', $output->apps[0]->id);
        self::assertSame(['nene-invoice'], $output->apps[1]->requires);

        self::assertArrayHasKey('nene-invoice', $output->versions);
        self::assertSame('1.3.0', $output->versions['nene-invoice']->installedVersion);
        self::assertSame('1.4.0', $output->versions['nene-invoice']->availableVersion);
        // An app without a mirror entry stays absent (handler renders it as null).
        self::assertArrayNotHasKey('nene-clear', $output->versions);
    }

    public function testReturnsEmptyVersionsWhenSourceHasNone(): void
    {
        $useCase = new ListCatalogAppsUseCase(
            new InMemoryCatalogAppRepository(new Catalog(1, [$this->invoice()])),
            new FakeCatalogAppVersionSource(),
        );

        $output = $useCase->execute(new DateTimeImmutable(self::NOW));

        self::assertSame([], $output->versions);
    }

    public function testPropagatesCatalogReadFailure(): void
    {
        $useCase = new ListCatalogAppsUseCase(
            new InMemoryCatalogAppRepository(null, new CatalogReadException('boom')),
            new FakeCatalogAppVersionSource(),
        );

        $this->expectException(CatalogReadException::class);

        $useCase->execute(new DateTimeImmutable(self::NOW));
    }

    private function invoice(): CatalogApp
    {
        return new CatalogApp(
            id: 'nene-invoice',
            name: 'NeNe Invoice',
            repository: 'hideyukiMORI/nene-invoice',
            path: 'nene-invoice',
            status: 'installable',
            requires: [],
            provides: ['billing-api'],
            installEntry: '/install/index.php',
            databaseEnvPrefix: 'NENE_INVOICE_DB_',
        );
    }

    private function clear(): CatalogApp
    {
        return new CatalogApp(
            id: 'nene-clear',
            name: 'NeNe Clear',
            repository: 'hideyukiMORI/nene-clear',
            path: 'nene-clear',
            status: 'installable',
            requires: ['nene-invoice'],
            provides: ['reconciliation-api'],
            installEntry: '/install/index.php',
            databaseEnvPrefix: 'NENE_CLEAR_DB_',
        );
    }
}
