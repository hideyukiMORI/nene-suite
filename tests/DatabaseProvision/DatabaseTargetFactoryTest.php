<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use InvalidArgumentException;
use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\DatabaseProvision\ExternalProvisionNotSupportedException;
use PHPUnit\Framework\TestCase;

final class DatabaseTargetFactoryTest extends TestCase
{
    public function testProvisionUsesConventionNameAndSuiteServer(): void
    {
        $target = $this->factory()->create('nene-invoice', DatabaseTargetMode::Provision, null, null);

        self::assertSame('nene-invoice', $target->catalogId);
        self::assertSame(DatabaseTargetMode::Provision, $target->mode);
        self::assertSame('nene_invoice', $target->databaseName);
        self::assertNull($target->server);
        self::assertFalse($target->isExternal());
    }

    public function testProvisionIgnoresNameOverride(): void
    {
        // The supplied name applies to adopt; provision always follows the convention.
        $target = $this->factory()->create('nene-invoice', DatabaseTargetMode::Provision, null, 'something_else');

        self::assertSame('nene_invoice', $target->databaseName);
    }

    public function testAdoptUsesSuppliedNameAndServer(): void
    {
        $target = $this->factory()->create('nene-invoice', DatabaseTargetMode::Adopt, 'legacy-db.internal', 'invoice_prod');

        self::assertSame(DatabaseTargetMode::Adopt, $target->mode);
        self::assertSame('invoice_prod', $target->databaseName);
        self::assertSame('legacy-db.internal', $target->server);
        self::assertTrue($target->isExternal());
    }

    public function testAdoptFallsBackToConventionNameWhenNameNull(): void
    {
        $target = $this->factory()->create('nene-invoice', DatabaseTargetMode::Adopt, null, null);

        self::assertSame('nene_invoice', $target->databaseName);
        self::assertNull($target->server);
    }

    public function testProvisionOnExternalServerIsRejected(): void
    {
        $this->expectException(ExternalProvisionNotSupportedException::class);

        $this->factory()->create('nene-invoice', DatabaseTargetMode::Provision, 'other-db.internal', null);
    }

    public function testUnsafeAdoptNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory()->create('nene-invoice', DatabaseTargetMode::Adopt, null, 'drop;table');
    }

    private function factory(): DatabaseTargetFactory
    {
        return new DatabaseTargetFactory(new AppDatabaseNamer());
    }
}
