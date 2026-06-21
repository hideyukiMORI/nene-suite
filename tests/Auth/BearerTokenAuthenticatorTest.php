<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeNeSuite\Auth\BearerTokenAuthenticator;
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
use Psr\Http\Message\ServerRequestInterface;

final class BearerTokenAuthenticatorTest extends TestCase
{
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    public function testReturnsOperatorIdFromValidToken(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue(['sub' => self::OPERATOR_ID, 'exp' => time() + 60]);

        $operatorId = $this->authenticator($verifier)->operatorId($this->request("Bearer {$token}"));

        self::assertSame(self::OPERATOR_ID, $operatorId);
    }

    public function testRejectsMissingHeader(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->authenticator(new LocalBearerTokenVerifier('test-secret'))->operatorId($this->request(''));
    }

    public function testRejectsInvalidToken(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->authenticator(new LocalBearerTokenVerifier('test-secret'))->operatorId($this->request('Bearer not-a-real-token'));
    }

    public function testRejectsTokenSignedWithDifferentSecret(): void
    {
        $token = (new LocalBearerTokenVerifier('other-secret'))->issue(['sub' => 'x', 'exp' => time() + 60]);

        $this->expectException(UnauthorizedException::class);

        $this->authenticator(new LocalBearerTokenVerifier('test-secret'))->operatorId($this->request("Bearer {$token}"));
    }

    public function testPrincipalReadsA6ClaimsWithoutResolving(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue([
            'sub' => self::OPERATOR_ID,
            'org_external_id' => self::ORG_EXTERNAL_ID,
            'role' => 'admin',
            'superadmin' => true,
            'exp' => time() + 60,
        ]);

        // Empty resolver: if the claims branch were skipped, role/superadmin would be null/false.
        $principal = $this->authenticator($verifier)->principal($this->request("Bearer {$token}"));

        self::assertSame(self::OPERATOR_ID, $principal->operatorId);
        self::assertSame(self::ORG_EXTERNAL_ID, $principal->orgExternalId);
        self::assertSame(Role::Admin, $principal->role);
        self::assertTrue($principal->isSuperadmin);
    }

    public function testPrincipalTreatsSuperadminFalseAndNullOrgAsAnA6Token(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        $token = $verifier->issue([
            'sub' => self::OPERATOR_ID,
            'org_external_id' => null,
            'role' => null,
            'superadmin' => false,
            'exp' => time() + 60,
        ]);

        // A resolver that WOULD return a context — proving the A6 branch wins (array_key_exists, not isset).
        $principal = $this->authenticator($verifier, $this->memberships(), $this->organizations())
            ->principal($this->request("Bearer {$token}"));

        self::assertNull($principal->orgExternalId);
        self::assertNull($principal->role);
        self::assertFalse($principal->isSuperadmin);
    }

    public function testPrincipalFallsBackToResolverForPreA6Token(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');
        // Pre-A6 token: no org_external_id/role/superadmin claims.
        $token = $verifier->issue(['sub' => self::OPERATOR_ID, 'exp' => time() + 60]);

        $principal = $this->authenticator($verifier, $this->memberships(), $this->organizations())
            ->principal($this->request("Bearer {$token}"));

        self::assertSame(self::ORG_EXTERNAL_ID, $principal->orgExternalId);
        self::assertSame(Role::Admin, $principal->role);
        self::assertFalse($principal->isSuperadmin);
    }

    private function authenticator(
        LocalBearerTokenVerifier $verifier,
        ?InMemoryMembershipRepository $memberships = null,
        ?InMemoryOrganizationRepository $organizations = null,
    ): BearerTokenAuthenticator {
        return new BearerTokenAuthenticator(
            $verifier,
            new OperatorSessionContextResolver(
                $memberships ?? new InMemoryMembershipRepository(),
                $organizations ?? new InMemoryOrganizationRepository(),
            ),
        );
    }

    private function memberships(): InMemoryMembershipRepository
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0ADM', self::OPERATOR_ID, self::ORG_ID, Role::Admin, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $memberships;
    }

    private function organizations(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXTERNAL_ID, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $organizations;
    }

    private function request(string $authorization): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/api/v1/auth/session');

        return $authorization === '' ? $request : $request->withHeader('Authorization', $authorization);
    }
}
