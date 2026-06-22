<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\ListSessionOrganizationsUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class ListSessionOrganizationsUseCaseTest extends TestCase
{
    private const OP = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_A = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const ORG_A_EXT = '01J8XRDEXT0000000000000ZAB';
    private const ORG_B = '01J8XR4ZS6Q9V2H7K3N5M0B8TD';
    private const ORG_B_EXT = '01J8XRDEXT0000000000000ZAC';
    private const ORG_C_MISSING = '01J8XR4ZS6Q9V2H7K3N5M0B8TF';

    public function testReturnsOrgScopedMembershipsWithRoleSkippingPlatformAndStale(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_A, self::ORG_A_EXT, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));
        $organizations->save(new Organization(self::ORG_B, self::ORG_B_EXT, 'Beta', 'beta', OrganizationStatus::Active, '2026-01-02T00:00:00Z', '2026-01-02T00:00:00Z'));

        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0SUP', self::OP, null, Role::Superadmin, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));
        $memberships->save(new Membership('01J0A', self::OP, self::ORG_A, Role::Admin, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));
        $memberships->save(new Membership('01J0B', self::OP, self::ORG_B, Role::Viewer, '2026-01-02T00:00:00Z', '2026-01-02T00:00:00Z'));
        $memberships->save(new Membership('01J0C', self::OP, self::ORG_C_MISSING, Role::Member, '2026-01-03T00:00:00Z', '2026-01-03T00:00:00Z'));

        $output = (new ListSessionOrganizationsUseCase($memberships, $organizations))->execute(self::OP);

        self::assertCount(2, $output->organizations);
        self::assertSame(self::ORG_A, $output->organizations[0]->organizationId);
        self::assertSame(self::ORG_A_EXT, $output->organizations[0]->externalId);
        self::assertSame(Role::Admin, $output->organizations[0]->role);
        self::assertSame(self::ORG_B, $output->organizations[1]->organizationId);
        self::assertSame(Role::Viewer, $output->organizations[1]->role);
    }

    public function testReturnsEmptyForOperatorWithoutOrgMemberships(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0SUP', self::OP, null, Role::Superadmin, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $output = (new ListSessionOrganizationsUseCase($memberships, new InMemoryOrganizationRepository()))->execute(self::OP);

        self::assertSame([], $output->organizations);
    }
}
