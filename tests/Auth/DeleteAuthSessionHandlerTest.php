<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\DeleteAuthSessionHandler;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAuthSessionHandlerTest extends TestCase
{
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const JTI = '01J0SESSION00000000000000ZA';

    public function testLogoutRevokesTheTokenJti(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OPERATOR_ID, 'jti' => self::JTI, 'exp' => time() + 60]);
        $revoked = new InMemoryRevokedTokenRepository();

        $response = $this->handler($verifier, $revoked)->handle($this->request("Bearer {$token}"));

        self::assertSame(204, $response->getStatusCode());
        self::assertTrue($revoked->isRevoked(self::JTI), 'logout must denylist the token jti');
    }

    public function testRejectsUnauthenticatedLogout(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler(new LocalBearerTokenVerifier('test-secret'), new InMemoryRevokedTokenRepository())
            ->handle($this->request(''));
    }

    public function testPreB13TokenWithoutJtiStillLogsOut(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OPERATOR_ID, 'exp' => time() + 60]);
        $revoked = new InMemoryRevokedTokenRepository();

        $response = $this->handler($verifier, $revoked)->handle($this->request("Bearer {$token}"));

        self::assertSame(204, $response->getStatusCode());
        self::assertFalse($revoked->isRevoked(self::JTI));
    }

    private function handler(LocalBearerTokenVerifier $verifier, InMemoryRevokedTokenRepository $revoked): DeleteAuthSessionHandler
    {
        $psr17 = new Psr17Factory();

        return new DeleteAuthSessionHandler(
            new BearerTokenAuthenticator(
                $verifier,
                new OperatorSessionContextResolver(new InMemoryMembershipRepository(), new InMemoryOrganizationRepository()),
                $revoked,
            ),
            $revoked,
            new JsonResponseFactory($psr17, $psr17),
        );
    }

    private function request(string $authorization): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('DELETE', '/api/v1/auth/session');

        return $authorization === '' ? $request : $request->withHeader('Authorization', $authorization);
    }
}
