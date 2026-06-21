<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\ListOrganizationMembershipsHandler;
use NeNeSuite\Tenancy\ListOrganizationMembershipsUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Auth\InMemoryOperatorRepository;
use PHPUnit\Framework\TestCase;

final class ListOrganizationMembershipsHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const ORG_EXT = '01J8XRDEXT0000000000000ZAB';
    private const OP_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const MEM_ID = '01J8XRMEM00000000000000ZAB';
    private const NOW = '2026-01-01T00:00:00Z';

    private function path(): string
    {
        return '/api/v1/organizations/' . self::ORG_ID . '/memberships';
    }

    public function testListsEnrichedMembersForSuperadmin(): void
    {
        $response = $this->handler($this->seededMembers())->handle(
            $this->request('GET', $this->path(), $this->superadminToken(), [], ['id' => self::ORG_ID]),
        );

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $members = $body['members'];
        self::assertIsArray($members);
        self::assertCount(1, $members);
        $member = $members[0];
        self::assertIsArray($member);
        self::assertSame(self::OP_ID, $member['operatorId']);
        self::assertSame('operator@example.com', $member['email']);
        self::assertSame('admin', $member['role']);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler($this->seededMembers())->handle(
            $this->request('GET', $this->path(), $this->nonSuperadminToken(), [], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler($this->seededMembers())->handle(
            $this->request('GET', $this->path(), null, [], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsUnknownOrganizationWithNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->handler(new InMemoryMembershipRepository(), new InMemoryOrganizationRepository())->handle(
            $this->request('GET', $this->path(), $this->superadminToken(), [], ['id' => self::ORG_ID]),
        );
    }

    private function seededMembers(): InMemoryMembershipRepository
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership(self::MEM_ID, self::OP_ID, self::ORG_ID, Role::Admin, self::NOW, self::NOW));

        return $memberships;
    }

    private function handler(
        ?InMemoryMembershipRepository $memberships = null,
        ?InMemoryOrganizationRepository $organizations = null,
    ): ListOrganizationMembershipsHandler {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP_ID, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        return new ListOrganizationMembershipsHandler(
            $this->guard(),
            new ListOrganizationMembershipsUseCase($memberships ?? new InMemoryMembershipRepository(), $operators),
            $organizations ?? $this->seededOrg(),
            $this->jsonResponseFactory(),
        );
    }

    private function seededOrg(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXT, 'Acme', 'acme', OrganizationStatus::Active, self::NOW, self::NOW));

        return $organizations;
    }
}
