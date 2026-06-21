<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class OperatorSessionContextResolverTest extends TestCase
{
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';
    private const ORG2_ID = '01J8XRDORG20000000000000ZA';
    private const ORG2_EXTERNAL_ID = '01J8XRDEXT20000000000000AB';

    public function testResolvesSuperadminWhoIsAlsoOrgAdmin(): void
    {
        $organizations = $this->organizations();
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership('01J0M1', null, Role::Superadmin, '2026-01-01T00:00:00Z'));
        $memberships->save($this->membership('01J0M2', self::ORG_ID, Role::Admin, '2026-02-01T00:00:00Z'));

        $context = (new OperatorSessionContextResolver($memberships, $organizations))->resolve(self::OPERATOR_ID);

        self::assertSame(self::ORG_EXTERNAL_ID, $context->orgExternalId);
        self::assertSame(Role::Admin, $context->role);
        self::assertTrue($context->isSuperadmin);
    }

    public function testResolvesOrgAdminOnly(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership('01J0M1', self::ORG_ID, Role::Admin, '2026-01-01T00:00:00Z'));

        $context = (new OperatorSessionContextResolver($memberships, $this->organizations()))->resolve(self::OPERATOR_ID);

        self::assertSame(self::ORG_EXTERNAL_ID, $context->orgExternalId);
        self::assertSame(Role::Admin, $context->role);
        self::assertFalse($context->isSuperadmin);
    }

    public function testResolvesMember(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership('01J0M1', self::ORG_ID, Role::Member, '2026-01-01T00:00:00Z'));

        $context = (new OperatorSessionContextResolver($memberships, $this->organizations()))->resolve(self::OPERATOR_ID);

        self::assertSame(Role::Member, $context->role);
        self::assertFalse($context->isSuperadmin);
    }

    public function testZeroMembershipResolvesToEmptyContext(): void
    {
        $context = (new OperatorSessionContextResolver(new InMemoryMembershipRepository(), $this->organizations()))
            ->resolve(self::OPERATOR_ID);

        self::assertNull($context->orgExternalId);
        self::assertNull($context->role);
        self::assertFalse($context->isSuperadmin);
    }

    public function testMultipleOrgMembershipsPicksOldest(): void
    {
        $memberships = new InMemoryMembershipRepository();
        // Seed NEWEST-first so the assertion fails unless the resolver/repo actually orders by
        // created_at (not insertion order): the older ORG_ID membership must win.
        $memberships->save($this->membership('01J0M2', self::ORG2_ID, Role::Admin, '2026-02-01T00:00:00Z'));
        $memberships->save($this->membership('01J0M1', self::ORG_ID, Role::Member, '2026-01-01T00:00:00Z'));

        $context = (new OperatorSessionContextResolver($memberships, $this->organizations()))->resolve(self::OPERATOR_ID);

        self::assertSame(self::ORG_EXTERNAL_ID, $context->orgExternalId, 'the oldest org membership is the active org');
        self::assertSame(Role::Member, $context->role);
    }

    public function testStaleOrgMembershipDegradesWithoutThrowing(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership('01J0M1', null, Role::Superadmin, '2026-01-01T00:00:00Z'));
        // Org-scoped membership pointing at an organization that no longer exists.
        $memberships->save($this->membership('01J0M2', self::ORG_ID, Role::Admin, '2026-02-01T00:00:00Z'));

        $context = (new OperatorSessionContextResolver($memberships, new InMemoryOrganizationRepository()))
            ->resolve(self::OPERATOR_ID);

        self::assertNull($context->orgExternalId);
        self::assertNull($context->role);
        self::assertTrue($context->isSuperadmin);
    }

    private function organizations(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXTERNAL_ID, 'Acme KK', 'acme-kk', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));
        $organizations->save(new Organization(self::ORG2_ID, self::ORG2_EXTERNAL_ID, 'Beta KK', 'beta-kk', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $organizations;
    }

    private function membership(string $id, ?string $organizationId, Role $role, string $createdAt): Membership
    {
        return new Membership($id, self::OPERATOR_ID, $organizationId, $role, $createdAt, $createdAt);
    }
}
