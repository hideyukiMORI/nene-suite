<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use NeNeSuite\Http\Edition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EditionTest extends TestCase
{
    public function testHostedOnlyForExactHostedString(): void
    {
        self::assertSame(Edition::Hosted, Edition::fromEnv('hosted'));
        self::assertTrue(Edition::fromEnv('hosted')->isHosted());
        self::assertFalse(Edition::fromEnv('hosted')->isOss());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function nonHostedValues(): iterable
    {
        yield 'unset' => [null];
        yield 'empty' => [''];
        yield 'oss' => ['oss'];
        yield 'uppercase HOSTED (case-sensitive)' => ['HOSTED'];
        yield 'whitespace padded' => [' hosted '];
        yield 'truthy one' => ['1'];
        yield 'truthy true' => ['true'];
        yield 'garbage' => ['enterprise'];
    }

    #[DataProvider('nonHostedValues')]
    public function testFailsClosedToOssForAnythingButHosted(?string $raw): void
    {
        self::assertSame(Edition::Oss, Edition::fromEnv($raw), 'edition must fail closed to oss');
        self::assertTrue(Edition::fromEnv($raw)->isOss());
        self::assertFalse(Edition::fromEnv($raw)->isHosted());
    }
}
