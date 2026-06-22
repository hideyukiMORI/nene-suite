<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\ListSessionOrganizationsHandler;
use NeNeSuite\Auth\ListSessionOrganizationsUseCase;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListSessionOrganizationsHandlerTest extends TestCase
{
    private const OP = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const ORG_EXT = '01J8XRDEXT0000000000000ZAB';
    private const NOW = '2026-01-01T00:00:00Z';

    public function testListsTheOperatorsOrganizations(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OP, 'exp' => time() + 60]);

        $response = $this->handler($verifier)->handle($this->request("Bearer {$token}"));

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $organizations = $body['organizations'];
        self::assertIsArray($organizations);
        self::assertCount(1, $organizations);
        $organization = $organizations[0];
        self::assertIsArray($organization);
        self::assertSame(self::ORG_ID, $organization['organizationId']);
        self::assertSame(self::ORG_EXT, $organization['externalId']);
        self::assertSame('admin', $organization['role']);
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler(new LocalBearerTokenVerifier('test-secret'))->handle(
            (new Psr17Factory())->createServerRequest('GET', '/api/v1/auth/session/organizations'),
        );
    }

    private function handler(LocalBearerTokenVerifier $verifier): ListSessionOrganizationsHandler
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXT, 'Acme', 'acme', OrganizationStatus::Active, self::NOW, self::NOW));
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0ADM', self::OP, self::ORG_ID, Role::Admin, self::NOW, self::NOW));

        $psr17 = new Psr17Factory();

        return new ListSessionOrganizationsHandler(
            new BearerTokenAuthenticator($verifier, new OperatorSessionContextResolver($memberships, $organizations), new InMemoryRevokedTokenRepository()),
            new ListSessionOrganizationsUseCase($memberships, $organizations),
            new JsonResponseFactory($psr17, $psr17),
        );
    }

    private function request(string $authorization): ServerRequestInterface
    {
        return (new Psr17Factory())
            ->createServerRequest('GET', '/api/v1/auth/session/organizations')
            ->withHeader('Authorization', $authorization);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
