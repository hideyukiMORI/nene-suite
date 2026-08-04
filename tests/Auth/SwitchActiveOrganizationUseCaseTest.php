<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\UtcClock;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\SwitchActiveOrganizationInput;
use NeNeSuite\Auth\SwitchActiveOrganizationUseCase;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Support\FixedClock;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class SwitchActiveOrganizationUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OP = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_A = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const ORG_A_EXT = '01J8XRDEXT0000000000000ZAB';
    private const ORG_B = '01J8XR4ZS6Q9V2H7K3N5M0B8TD';
    private const ORG_B_EXT = '01J8XRDEXT0000000000000ZAC';
    private const NOW = '2026-01-01T00:00:00Z';

    public function testIssuesNewTokenForTargetOrgPreservingSuperadmin(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');

        $output = $this->useCase($verifier)->execute(new SwitchActiveOrganizationInput(self::OP, self::ORG_B));

        self::assertSame(self::ORG_B_EXT, $output->orgExternalId);
        self::assertSame(Role::Viewer, $output->role);
        self::assertTrue($output->superadmin);
        self::assertGreaterThan(time(), $output->expiresAt);

        $claims = $verifier->verify($output->token);
        self::assertSame(self::OP, $claims['sub']);
        self::assertSame(self::SUITE_ID, $claims['suite_id']);
        self::assertSame(self::ORG_B_EXT, $claims['org_external_id']);
        self::assertSame('viewer', $claims['role']);
        self::assertTrue($claims['superadmin']);
    }

    public function testRecomputesSuperadminAsFalseForNonPlatformOperator(): void
    {
        $verifier = new LocalBearerTokenVerifier('test-secret');

        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_B, self::ORG_B_EXT, 'Beta', 'beta', OrganizationStatus::Active, self::NOW, self::NOW));

        // Only an org-scoped membership — no platform (null-org) superadmin membership.
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0B', self::OP, self::ORG_B, Role::Member, self::NOW, self::NOW));

        $output = (new SwitchActiveOrganizationUseCase($operators, $memberships, $organizations, $verifier, self::SUITE_ID))
            ->execute(new SwitchActiveOrganizationInput(self::OP, self::ORG_B));

        self::assertSame(self::ORG_B_EXT, $output->orgExternalId);
        self::assertSame(Role::Member, $output->role);
        self::assertFalse($output->superadmin);

        $claims = $verifier->verify($output->token);
        self::assertFalse($claims['superadmin']);
        self::assertSame('member', $claims['role']);
    }

    public function testRejectsOrganizationTheOperatorIsNotAMemberOf(): void
    {
        // ORG_A exists but the operator has no membership in it.
        $this->expectException(OrganizationNotFoundException::class);

        $this->useCase(new LocalBearerTokenVerifier('test-secret'))
            ->execute(new SwitchActiveOrganizationInput(self::OP, self::ORG_A));
    }

    public function testRejectsUnknownOperator(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->useCase(new LocalBearerTokenVerifier('test-secret'))
            ->execute(new SwitchActiveOrganizationInput('01J8XR4ZS6Q9V2H7K3N5M0B8TE', self::ORG_B));
    }

    public function testRefreshedTokenGetsAFullTtlFromTheInjectedClock(): void
    {
        // A switch is a session refresh, not an extension of the prior token — the new `exp` must
        // be a full TTL from the switch instant, which only a pinned clock can assert exactly.
        $clock = new FixedClock('2026-08-04T12:00:00Z');
        $verifier = new LocalBearerTokenVerifier('test-secret', $clock);

        $output = $this->useCase($verifier, $clock)->execute(new SwitchActiveOrganizationInput(self::OP, self::ORG_B));

        $switchedAt = $clock->timestamp();
        $claims = $verifier->verify($output->token);

        self::assertSame($switchedAt, $claims['iat']);
        self::assertSame($switchedAt + 86400, $claims['exp']);
        self::assertSame($claims['exp'], $output->expiresAt);
    }

    private function useCase(LocalBearerTokenVerifier $verifier, ?FixedClock $clock = null): SwitchActiveOrganizationUseCase
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_A, self::ORG_A_EXT, 'Acme', 'acme', OrganizationStatus::Active, self::NOW, self::NOW));
        $organizations->save(new Organization(self::ORG_B, self::ORG_B_EXT, 'Beta', 'beta', OrganizationStatus::Active, self::NOW, self::NOW));

        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0SUP', self::OP, null, Role::Superadmin, self::NOW, self::NOW));
        $memberships->save(new Membership('01J0B', self::OP, self::ORG_B, Role::Viewer, self::NOW, self::NOW));

        return new SwitchActiveOrganizationUseCase(
            $operators,
            $memberships,
            $organizations,
            $verifier,
            self::SUITE_ID,
            $clock ?? new UtcClock(),
        );
    }
}
