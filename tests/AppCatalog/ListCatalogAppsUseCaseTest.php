<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\AppCatalog\CatalogReadException;
use NeNeSuite\AppCatalog\ListCatalogAppsUseCase;
use PHPUnit\Framework\TestCase;

final class ListCatalogAppsUseCaseTest extends TestCase
{
    public function testReturnsCatalogVersionAndApps(): void
    {
        $invoice = new CatalogApp(
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
        $clear = new CatalogApp(
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

        $useCase = new ListCatalogAppsUseCase(
            new InMemoryCatalogAppRepository(new Catalog(1, [$invoice, $clear])),
        );

        $output = $useCase->execute();

        self::assertSame(1, $output->version);
        self::assertCount(2, $output->apps);
        self::assertSame('nene-invoice', $output->apps[0]->id);
        self::assertSame(['nene-invoice'], $output->apps[1]->requires);
    }

    public function testPropagatesCatalogReadFailure(): void
    {
        $useCase = new ListCatalogAppsUseCase(
            new InMemoryCatalogAppRepository(null, new CatalogReadException('boom')),
        );

        $this->expectException(CatalogReadException::class);

        $useCase->execute();
    }
}
