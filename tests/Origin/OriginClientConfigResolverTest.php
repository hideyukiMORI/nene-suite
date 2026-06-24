<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginClientConfigResolver;
use PHPUnit\Framework\TestCase;

final class OriginClientConfigResolverTest extends TestCase
{
    private const ENV_BASE_URL = 'NENE_ORIGIN_URL';

    private const ENV_TRUST_ANCHOR_PATH = 'NENE_ORIGIN_TRUST_ANCHOR_PATH';

    private const ENV_ROOT_VERSION_FLOOR = 'NENE_ORIGIN_ROOT_VERSION_FLOOR';

    private const ENV_GEN_FLOOR = 'NENE_ORIGIN_GEN_FLOOR';

    private const ENV_VARS = [
        self::ENV_BASE_URL,
        self::ENV_TRUST_ANCHOR_PATH,
        self::ENV_ROOT_VERSION_FLOOR,
        self::ENV_GEN_FLOOR,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearEnv();
    }

    protected function tearDown(): void
    {
        $this->clearEnv();
        parent::tearDown();
    }

    public function testResolvesBaseUrlFloorsAndAnchorPath(): void
    {
        $_SERVER[self::ENV_BASE_URL] = 'https://origin.example.com/';
        $_SERVER[self::ENV_TRUST_ANCHOR_PATH] = '/etc/nene/trust-anchor.json';
        $_SERVER[self::ENV_ROOT_VERSION_FLOOR] = '3';
        $_SERVER[self::ENV_GEN_FLOOR] = '12';

        $config = (new OriginClientConfigResolver())->resolve();

        self::assertSame('https://origin.example.com', $config->baseUrl);
        self::assertSame('/etc/nene/trust-anchor.json', $config->trustAnchorPath);
        self::assertSame(3, $config->rootVersionFloor);
        self::assertSame(12, $config->genFloor);
        self::assertSame(10, $config->timeoutSeconds);
        self::assertTrue($config->isEnabled());
    }

    public function testFloorsDefaultToOne(): void
    {
        $_SERVER[self::ENV_BASE_URL] = 'https://origin.example.com';

        $config = (new OriginClientConfigResolver())->resolve();

        self::assertSame(1, $config->rootVersionFloor);
        self::assertSame(1, $config->genFloor);
    }

    public function testDisabledWithoutTrustAnchor(): void
    {
        // A base URL alone is not enough — without an embedded trust anchor the client stays disabled.
        $_SERVER[self::ENV_BASE_URL] = 'https://origin.example.com';

        $config = (new OriginClientConfigResolver())->resolve();

        self::assertNull($config->trustAnchorPath);
        self::assertFalse($config->isEnabled());
    }

    public function testDisabledWhenUnset(): void
    {
        $config = (new OriginClientConfigResolver())->resolve();

        self::assertSame('', $config->baseUrl);
        self::assertFalse($config->isEnabled());
    }

    private function clearEnv(): void
    {
        foreach (self::ENV_VARS as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }
    }
}
