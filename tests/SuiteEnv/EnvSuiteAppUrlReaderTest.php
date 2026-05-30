<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteEnv;

use NeNeSuite\SuiteEnv\EnvSuiteAppUrlReader;
use PHPUnit\Framework\TestCase;

final class EnvSuiteAppUrlReaderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['NENE_SUITE_APP_NENE_INVOICE_URL'], $_ENV['NENE_SUITE_APP_NENE_INVOICE_URL']);

        parent::tearDown();
    }

    public function testReadsSnakeUppercaseEnvKey(): void
    {
        $_SERVER['NENE_SUITE_APP_NENE_INVOICE_URL'] = 'https://example.com/nene-invoice/';

        self::assertSame(
            'https://example.com/nene-invoice/',
            (new EnvSuiteAppUrlReader())->publicUrl('nene-invoice'),
        );
    }

    public function testReturnsNullWhenUnset(): void
    {
        self::assertNull((new EnvSuiteAppUrlReader())->publicUrl('nene-clear'));
    }
}
