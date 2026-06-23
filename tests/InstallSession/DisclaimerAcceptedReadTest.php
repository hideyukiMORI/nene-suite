<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\PdoInstallSessionRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The control DB stores disclaimer_accepted as a boolean. MySQL/SQLite read it back as
 * 1/0, but PostgreSQL returns 't'/'f' — a naive (bool) cast turns 'f' into true (ADR 0016).
 * Pins the engine-agnostic normalizer, especially the PostgreSQL 'f' => false case.
 */
final class DisclaimerAcceptedReadTest extends TestCase
{
    #[DataProvider('valueProvider')]
    public function testToBoolNormalizesAcrossEngines(mixed $stored, bool $expected): void
    {
        $method = new ReflectionMethod(PdoInstallSessionRepository::class, 'toBool');

        self::assertSame($expected, $method->invoke(null, $stored));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function valueProvider(): array
    {
        return [
            'pgsql true'      => ['t', true],
            'pgsql false'     => ['f', false],
            'mysql/sqlite 1'  => ['1', true],
            'mysql/sqlite 0'  => ['0', false],
            'int 1'           => [1, true],
            'int 0'           => [0, false],
            'bool true'       => [true, true],
            'bool false'      => [false, false],
            'string true'     => ['true', true],
            'string false'    => ['false', false],
            'empty string'    => ['', false],
        ];
    }
}
