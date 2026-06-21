<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\GetAuthSessionHandler;
use NeNeSuite\Auth\GetAuthSessionUseCase;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class GetAuthSessionHandlerTest extends TestCase
{
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    public function testReturnsOperatorIdentityPlusContextFromA6Token(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        // Claims say superadmin=true; the seeded resolver would say false — proving the claims path wins.
        $token = $verifier->issue([
            'sub' => self::OPERATOR_ID,
            'org_external_id' => self::ORG_EXTERNAL_ID,
            'role' => 'admin',
            'superadmin' => true,
            'exp' => time() + 60,
        ]);

        $body = $this->handle($verifier, $token);

        self::assertSame(self::OPERATOR_ID, $body['id']);
        self::assertSame('operator@example.com', $body['email']);
        self::assertSame('Example Operator', $body['displayName']);
        self::assertSame(self::ORG_EXTERNAL_ID, $body['orgExternalId']);
        self::assertSame('admin', $body['role']);
        self::assertTrue($body['superadmin']);
    }

    public function testFallsBackToResolverForPreA6Token(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OPERATOR_ID, 'exp' => time() + 60]);

        $body = $this->handle($verifier, $token);

        self::assertSame(self::OPERATOR_ID, $body['id']);
        self::assertSame(self::ORG_EXTERNAL_ID, $body['orgExternalId']);
        self::assertSame('admin', $body['role']);
        self::assertFalse($body['superadmin'], 'fallback resolver sees an admin (not superadmin) membership');
    }

    /**
     * @return array<string, mixed>
     */
    private function handle(LocalBearerTokenVerifier $verifier, string $token): array
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OPERATOR_ID, 'operator@example.com', 'hash', 'Example Operator', '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXTERNAL_ID, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0ADM', self::OPERATOR_ID, self::ORG_ID, Role::Admin, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $psr17 = new Psr17Factory();
        $handler = new GetAuthSessionHandler(
            new BearerTokenAuthenticator($verifier, new OperatorSessionContextResolver($memberships, $organizations)),
            new GetAuthSessionUseCase($operators),
            new JsonResponseFactory($psr17, $psr17),
        );

        $decoded = json_decode((string) $handler->handle($this->request("Bearer {$token}"))->getBody(), true);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function request(string $authorization): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest('GET', '/api/v1/auth/session')->withHeader('Authorization', $authorization);
    }
}
