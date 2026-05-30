<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use PHPUnit\Framework\TestCase;

final class AppDatabaseNamerTest extends TestCase
{
    public function testReplacesHyphensWithUnderscores(): void
    {
        $namer = new AppDatabaseNamer();

        self::assertSame('nene_invoice', $namer->databaseName('nene-invoice'));
        self::assertSame('nene_clear', $namer->databaseName('nene-clear'));
        self::assertSame('nene_records', $namer->databaseName('nene-records'));
    }
}
