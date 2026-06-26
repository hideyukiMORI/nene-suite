<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallManifest;

use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\InstallManifest\InstallManifestApp;
use NeNeSuite\InstallManifest\InstallManifestFactory;
use PHPUnit\Framework\TestCase;

final class InstallManifestFactoryTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDEV0FED0000000000ZAB';

    public function testProvisionAppEntryOmitsTargetDefaults(): void
    {
        // A provision-on-suite-server app stays byte-identical to the historical shape:
        // mode (provision) and server (suite) are omitted (ADR 0021).
        $manifest = (new InstallManifestFactory())->create(
            self::SUITE_ID,
            self::ORG_ID,
            null,
            null,
            [new InstallManifestApp('nene-invoice', 'https://example.com/nene-invoice/', 'nene_invoice')],
        );

        self::assertSame([
            'catalog_id' => 'nene-invoice',
            'public_url' => 'https://example.com/nene-invoice/',
            'database_name' => 'nene_invoice',
        ], $manifest->body['apps'][0]);
    }

    public function testAdoptAppEntryCarriesModeAndServer(): void
    {
        $manifest = (new InstallManifestFactory())->create(
            self::SUITE_ID,
            self::ORG_ID,
            null,
            null,
            [new InstallManifestApp(
                'nene-invoice',
                'https://example.com/nene-invoice/',
                'invoice_prod',
                DatabaseTargetMode::Adopt,
                'legacy-db.internal',
            )],
        );

        self::assertSame([
            'catalog_id' => 'nene-invoice',
            'public_url' => 'https://example.com/nene-invoice/',
            'database_name' => 'invoice_prod',
            'mode' => 'adopt',
            'server' => 'legacy-db.internal',
        ], $manifest->body['apps'][0]);
    }

    public function testAdoptOnSuiteServerOmitsServerButKeepsMode(): void
    {
        $manifest = (new InstallManifestFactory())->create(
            self::SUITE_ID,
            self::ORG_ID,
            null,
            null,
            [new InstallManifestApp(
                'nene-invoice',
                'https://example.com/nene-invoice/',
                'nene_invoice',
                DatabaseTargetMode::Adopt,
            )],
        );

        self::assertSame([
            'catalog_id' => 'nene-invoice',
            'public_url' => 'https://example.com/nene-invoice/',
            'database_name' => 'nene_invoice',
            'mode' => 'adopt',
        ], $manifest->body['apps'][0]);
    }
}
