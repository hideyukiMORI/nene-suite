<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use NeNeSuite\AppCatalog\CatalogReadException;
use NeNeSuite\AppCatalog\JsonFileCatalogAppRepository;
use PHPUnit\Framework\TestCase;

final class JsonFileCatalogAppRepositoryTest extends TestCase
{
    public function testLoadsRealRepositoryCatalog(): void
    {
        $catalogPath = dirname(__DIR__, 2) . '/catalog/apps.json';

        $catalog = (new JsonFileCatalogAppRepository($catalogPath))->load();

        self::assertGreaterThanOrEqual(1, $catalog->version);
        self::assertNotEmpty($catalog->apps);

        $invoice = null;
        foreach ($catalog->apps as $app) {
            if ($app->id === 'nene-invoice') {
                $invoice = $app;
                break;
            }
        }

        self::assertNotNull($invoice, 'nene-invoice must be present in the catalog.');
        self::assertSame('NeNe Invoice', $invoice->name);
        self::assertSame('/install/index.php', $invoice->installEntry);
        self::assertSame('NENE_INVOICE_DB_', $invoice->databaseEnvPrefix);
    }

    public function testMapsSnakeCaseFieldsToCamelCase(): void
    {
        $path = $this->writeTempCatalog(<<<'JSON'
            {
              "version": 3,
              "apps": [
                {
                  "id": "nene-invoice",
                  "name": "NeNe Invoice",
                  "repository": null,
                  "path": "nene-invoice",
                  "status": "installable",
                  "requires": [],
                  "provides": ["billing-api"],
                  "install_entry": "/install/index.php",
                  "database": { "env_prefix": "NENE_INVOICE_DB_" }
                }
              ]
            }
            JSON);

        try {
            $catalog = (new JsonFileCatalogAppRepository($path))->load();
        } finally {
            unlink($path);
        }

        self::assertSame(3, $catalog->version);
        self::assertCount(1, $catalog->apps);
        self::assertNull($catalog->apps[0]->repository);
        self::assertSame('/install/index.php', $catalog->apps[0]->installEntry);
        self::assertSame('NENE_INVOICE_DB_', $catalog->apps[0]->databaseEnvPrefix);
    }

    public function testMissingFileThrows(): void
    {
        $repository = new JsonFileCatalogAppRepository('/no/such/catalog/apps.json');

        $this->expectException(CatalogReadException::class);

        $repository->load();
    }

    public function testInvalidJsonThrows(): void
    {
        $path = $this->writeTempCatalog('{ not valid json');

        try {
            $this->expectException(CatalogReadException::class);
            (new JsonFileCatalogAppRepository($path))->load();
        } finally {
            unlink($path);
        }
    }

    private function writeTempCatalog(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nene-suite-catalog-');

        if ($path === false) {
            self::fail('Could not create temp catalog file.');
        }

        file_put_contents($path, $contents);

        return $path;
    }
}
