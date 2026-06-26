<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use InvalidArgumentException;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use PHPUnit\Framework\TestCase;

final class DatabaseTargetModeTest extends TestCase
{
    public function testFromStringParsesCanonicalValues(): void
    {
        self::assertSame(DatabaseTargetMode::Provision, DatabaseTargetMode::fromString('provision'));
        self::assertSame(DatabaseTargetMode::Adopt, DatabaseTargetMode::fromString('adopt'));
    }

    public function testFromStringRejectsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabaseTargetMode::fromString('Provision');
    }
}
