<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use InvalidArgumentException;
use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\DatabaseProvision\EnvDatabaseTargetResolver;
use NeNeSuite\DatabaseProvision\ExternalProvisionNotSupportedException;
use PHPUnit\Framework\TestCase;

final class EnvDatabaseTargetResolverTest extends TestCase
{
    private const KEYS = [
        'NENE_SUITE_APP_NENE_INVOICE_DB_MODE',
        'NENE_SUITE_APP_NENE_INVOICE_DB_SERVER',
        'NENE_SUITE_APP_NENE_INVOICE_DB_NAME',
    ];

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            unset($_SERVER[$key], $_ENV[$key]);
        }

        parent::tearDown();
    }

    public function testDefaultsToSuiteProvisionTargetWhenUnset(): void
    {
        $target = $this->resolver()->resolve('nene-invoice');

        self::assertSame('nene-invoice', $target->catalogId);
        self::assertSame(DatabaseTargetMode::Provision, $target->mode);
        self::assertSame('nene_invoice', $target->databaseName);
        self::assertNull($target->server);
        self::assertFalse($target->isExternal());
    }

    public function testAdoptOnExternalServerWithExistingName(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_MODE'] = 'adopt';
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_SERVER'] = 'legacy-db.internal';
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_NAME'] = 'invoice_prod';

        $target = $this->resolver()->resolve('nene-invoice');

        self::assertSame(DatabaseTargetMode::Adopt, $target->mode);
        self::assertSame('invoice_prod', $target->databaseName);
        self::assertSame('legacy-db.internal', $target->server);
        self::assertTrue($target->isExternal());
    }

    public function testAdoptFallsBackToConventionNameWhenNameUnset(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_MODE'] = 'adopt';

        $target = $this->resolver()->resolve('nene-invoice');

        self::assertSame(DatabaseTargetMode::Adopt, $target->mode);
        self::assertSame('nene_invoice', $target->databaseName);
        self::assertNull($target->server);
    }

    public function testProvisionModeIgnoresNameOverride(): void
    {
        // The _DB_NAME override applies to adopt; provision always follows the convention.
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_MODE'] = 'provision';
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_NAME'] = 'something_else';

        $target = $this->resolver()->resolve('nene-invoice');

        self::assertSame('nene_invoice', $target->databaseName);
    }

    public function testProvisionWithExternalServerIsRejected(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_SERVER'] = 'other-db.internal';

        $this->expectException(ExternalProvisionNotSupportedException::class);

        $this->resolver()->resolve('nene-invoice');
    }

    public function testUnknownModeThrows(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_MODE'] = 'mirror';

        $this->expectException(InvalidArgumentException::class);

        $this->resolver()->resolve('nene-invoice');
    }

    public function testUnsafeAdoptNameThrows(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_MODE'] = 'adopt';
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_DB_NAME'] = 'drop;table';

        $this->expectException(InvalidArgumentException::class);

        $this->resolver()->resolve('nene-invoice');
    }

    private function resolver(): EnvDatabaseTargetResolver
    {
        return new EnvDatabaseTargetResolver(new DatabaseTargetFactory(new AppDatabaseNamer()));
    }
}
