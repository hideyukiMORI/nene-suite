<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use NeNeSuite\Deploy\DeployAgentConfigResolver;
use PHPUnit\Framework\TestCase;

final class DeployAgentConfigResolverTest extends TestCase
{
    private const STRONG_KEY = 'dev-agent-key-0123456789abcdef01'; // 32 bytes

    protected function tearDown(): void
    {
        unset(
            $_SERVER['NENE_SUITE_DEPLOY_AGENT_ENABLED'],
            $_SERVER['NENE_SUITE_DEPLOY_AGENT_KEY'],
            $_ENV['NENE_SUITE_DEPLOY_AGENT_ENABLED'],
            $_ENV['NENE_SUITE_DEPLOY_AGENT_KEY'],
        );
    }

    public function testDisabledByDefault(): void
    {
        $config = (new DeployAgentConfigResolver())->resolve();

        self::assertFalse($config->enabled);
        self::assertSame('', $config->agentKey);
    }

    public function testDisabledWhenFlagIsNotTheExactStringOne(): void
    {
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_ENABLED'] = 'true';
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_KEY'] = self::STRONG_KEY;

        self::assertFalse((new DeployAgentConfigResolver())->resolve()->enabled);
    }

    public function testDisabledWhenKeyIsMissing(): void
    {
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_ENABLED'] = '1';

        self::assertFalse((new DeployAgentConfigResolver())->resolve()->enabled);
    }

    public function testDisabledWhenKeyIsTooShort(): void
    {
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_ENABLED'] = '1';
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_KEY'] = 'short-key';

        $config = (new DeployAgentConfigResolver())->resolve();

        self::assertFalse($config->enabled);
        self::assertSame('', $config->agentKey);
    }

    public function testEnabledWithFlagAndStrongKey(): void
    {
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_ENABLED'] = '1';
        $_SERVER['NENE_SUITE_DEPLOY_AGENT_KEY'] = self::STRONG_KEY;

        $config = (new DeployAgentConfigResolver())->resolve();

        self::assertTrue($config->enabled);
        self::assertSame(self::STRONG_KEY, $config->agentKey);
    }
}
