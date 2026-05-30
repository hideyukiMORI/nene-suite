<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\UnauthorizedException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class BearerTokenAuthenticatorTest extends TestCase
{
    public function testReturnsOperatorIdFromValidToken(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => '01J8XR0G7Q9V2H7K3N5M0B8TCA', 'exp' => time() + 60]);

        $operatorId = (new BearerTokenAuthenticator($verifier))->operatorId($this->request("Bearer {$token}"));

        self::assertSame('01J8XR0G7Q9V2H7K3N5M0B8TCA', $operatorId);
    }

    public function testRejectsMissingHeader(): void
    {
        $this->expectException(UnauthorizedException::class);

        (new BearerTokenAuthenticator(new LocalBearerTokenVerifier('test-secret')))->operatorId($this->request(''));
    }

    public function testRejectsInvalidToken(): void
    {
        $this->expectException(UnauthorizedException::class);

        (new BearerTokenAuthenticator(new LocalBearerTokenVerifier('test-secret')))
            ->operatorId($this->request('Bearer not-a-real-token'));
    }

    public function testRejectsTokenSignedWithDifferentSecret(): void
    {
        $token = (new LocalBearerTokenVerifier('other-secret'))->issue(['sub' => 'x', 'exp' => time() + 60]);

        $this->expectException(UnauthorizedException::class);

        (new BearerTokenAuthenticator(new LocalBearerTokenVerifier('test-secret')))
            ->operatorId($this->request("Bearer {$token}"));
    }

    private function request(string $authorization): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/api/v1/auth/session');

        return $authorization === '' ? $request : $request->withHeader('Authorization', $authorization);
    }
}
