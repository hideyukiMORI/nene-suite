<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\ClientIpResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ClientIpResolverTest extends TestCase
{
    public function testUsesRemoteAddrAndIgnoresForwardedWhenNoTrustedProxies(): void
    {
        $resolver = new ClientIpResolver([]);

        self::assertSame('198.51.100.4', $resolver->resolve($this->request('198.51.100.4', '203.0.113.9')));
    }

    public function testIgnoresForwardedWhenRemoteAddrIsNotATrustedProxy(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame('198.51.100.4', $resolver->resolve($this->request('198.51.100.4', '203.0.113.9')));
    }

    public function testUsesForwardedWhenRemoteAddrIsATrustedProxy(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame('203.0.113.9', $resolver->resolve($this->request('10.4.5.6', '203.0.113.9')));
    }

    public function testTakesRightmostUntrustedForwardedHop(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.0/8']);

        self::assertSame('203.0.113.9', $resolver->resolve($this->request('10.4.5.6', '203.0.113.9, 10.0.0.9')));
    }

    public function testMatchesIpv6Cidr(): void
    {
        $resolver = new ClientIpResolver(['fd00::/8']);

        self::assertSame('2001:db8::1', $resolver->resolve($this->request('fd00::1', '2001:db8::1')));
    }

    public function testParseTrustedProxiesSplitsAndTrims(): void
    {
        self::assertSame(
            ['10.0.0.0/8', '192.168.0.0/16'],
            ClientIpResolver::parseTrustedProxies(' 10.0.0.0/8 , 192.168.0.0/16 , '),
        );
        self::assertSame([], ClientIpResolver::parseTrustedProxies(''));
    }

    private function request(string $remoteAddr, string $forwardedFor): ServerRequestInterface
    {
        return (new Psr17Factory())
            ->createServerRequest('POST', '/api/v1/auth/session', ['REMOTE_ADDR' => $remoteAddr])
            ->withHeader('X-Forwarded-For', $forwardedFor);
    }
}
