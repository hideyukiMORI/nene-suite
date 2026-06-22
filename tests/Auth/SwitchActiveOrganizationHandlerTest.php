<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Auth\SwitchActiveOrganizationHandler;
use NeNeSuite\Auth\SwitchActiveOrganizationUseCase;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SwitchActiveOrganizationHandlerTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OP = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_B = '01J8XR4ZS6Q9V2H7K3N5M0B8TD';
    private const ORG_B_EXT = '01J8XRDEXT0000000000000ZAC';
    private const ORG_UNKNOWN = '01J8XR4ZS6Q9V2H7K3N5M0B8TE';
    private const NOW = '2026-01-01T00:00:00Z';

    public function testSwitchesActiveOrgAndReturnsNewSession(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OP, 'exp' => time() + 60]);

        $response = $this->handler($verifier)->handle($this->request("Bearer {$token}", ['organizationId' => self::ORG_B]));

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        self::assertIsString($body['token']);
        self::assertSame(self::ORG_B_EXT, $body['orgExternalId']);
        self::assertSame('viewer', $body['role']);
        self::assertSame(self::OP, $body['operator']['id']);
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler(new LocalBearerTokenVerifier('test-secret'))->handle($this->request(null, ['organizationId' => self::ORG_B]));
    }

    public function testRejectsInvalidOrganizationIdWithValidation(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OP, 'exp' => time() + 60]);

        $this->expectException(ValidationException::class);

        $this->handler($verifier)->handle($this->request("Bearer {$token}", ['organizationId' => 'not-a-ulid']));
    }

    public function testRejectsOrganizationTheOperatorIsNotAMemberOf(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OP, 'exp' => time() + 60]);

        $this->expectException(OrganizationNotFoundException::class);

        $this->handler($verifier)->handle($this->request("Bearer {$token}", ['organizationId' => self::ORG_UNKNOWN]));
    }

    private function handler(LocalBearerTokenVerifier $verifier): SwitchActiveOrganizationHandler
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_B, self::ORG_B_EXT, 'Beta', 'beta', OrganizationStatus::Active, self::NOW, self::NOW));

        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0B', self::OP, self::ORG_B, Role::Viewer, self::NOW, self::NOW));

        $psr17 = new Psr17Factory();

        return new SwitchActiveOrganizationHandler(
            new BearerTokenAuthenticator($verifier, new OperatorSessionContextResolver($memberships, $organizations), new InMemoryRevokedTokenRepository()),
            new SwitchActiveOrganizationUseCase($operators, $memberships, $organizations, $verifier, self::SUITE_ID),
            new JsonResponseFactory($psr17, $psr17),
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(?string $authorization, array $body): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('PUT', '/api/v1/auth/session/active-organization');

        if ($authorization !== null) {
            $request = $request->withHeader('Authorization', $authorization);
        }

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream((string) json_encode($body)));
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
