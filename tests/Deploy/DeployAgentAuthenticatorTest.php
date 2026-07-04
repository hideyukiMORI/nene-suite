<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use NeNeSuite\Deploy\DeployAgentAuthenticator;
use NeNeSuite\Deploy\DeployAgentConfig;
use NeNeSuite\Deploy\DeployAgentUnauthorizedException;
use NeNeSuite\Deploy\DeployCapabilityDisabledException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class DeployAgentAuthenticatorTest extends TestCase
{
    private const KEY = 'dev-agent-key-0123456789abcdef01';

    public function testThrowsCapabilityDisabledBeforeReadingTheKey(): void
    {
        $authenticator = new DeployAgentAuthenticator(DeployAgentConfig::disabled());

        $this->expectException(DeployCapabilityDisabledException::class);

        $authenticator->ensure($this->request(self::KEY));
    }

    public function testRejectsMissingKey(): void
    {
        $authenticator = new DeployAgentAuthenticator(new DeployAgentConfig(true, self::KEY));

        $this->expectException(DeployAgentUnauthorizedException::class);

        $authenticator->ensure($this->request(null));
    }

    public function testRejectsMismatchedKey(): void
    {
        $authenticator = new DeployAgentAuthenticator(new DeployAgentConfig(true, self::KEY));

        $this->expectException(DeployAgentUnauthorizedException::class);

        $authenticator->ensure($this->request('wrong-key-0123456789abcdef012345'));
    }

    public function testAcceptsMatchingKey(): void
    {
        $authenticator = new DeployAgentAuthenticator(new DeployAgentConfig(true, self::KEY));

        $authenticator->ensure($this->request(self::KEY));

        $this->addToAssertionCount(1);
    }

    private function request(?string $key): ServerRequest
    {
        $request = new ServerRequest('GET', '/api/v1/machine/deploy/requests/pending');

        return $key === null ? $request : $request->withHeader(DeployAgentAuthenticator::HEADER, $key);
    }
}
