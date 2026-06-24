<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginClientConfigResolver;
use PHPUnit\Framework\TestCase;

final class OriginClientConfigResolverTest extends TestCase
{
    private const ENV_VAR = 'NENE_ORIGIN_URL';

    protected function setUp(): void
    {
        parent::setUp();
        // Deterministic regardless of any ambient .env value.
        unset($_SERVER[self::ENV_VAR], $_ENV[self::ENV_VAR]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER[self::ENV_VAR], $_ENV[self::ENV_VAR]);
        parent::tearDown();
    }

    public function testResolvesBaseUrlAndTrimsTrailingSlash(): void
    {
        $_SERVER[self::ENV_VAR] = 'https://origin.example.com/';

        $config = (new OriginClientConfigResolver())->resolve();

        self::assertSame('https://origin.example.com', $config->baseUrl);
        self::assertTrue($config->isEnabled());
        self::assertSame(10, $config->timeoutSeconds);
    }

    public function testDisabledWhenUnset(): void
    {
        $config = (new OriginClientConfigResolver())->resolve();

        self::assertSame('', $config->baseUrl);
        self::assertFalse($config->isEnabled());
    }
}
