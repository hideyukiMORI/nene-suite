<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\Operator;
use NeNeSuite\Tenancy\ListOrganizationMembershipsUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Auth\InMemoryOperatorRepository;
use PHPUnit\Framework\TestCase;

final class ListOrganizationMembershipsUseCaseTest extends TestCase
{
    private const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const OP_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const MEM_ID = '01J8XRMEM00000000000000ZAB';
    private const NOW = '2026-01-01T00:00:00Z';

    public function testEnrichesMembershipsWithOperatorIdentity(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership(self::MEM_ID, self::OP_ID, self::ORG_ID, Role::Admin, self::NOW, self::NOW));

        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP_ID, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        $output = (new ListOrganizationMembershipsUseCase($memberships, $operators))->execute(self::ORG_ID);

        self::assertCount(1, $output->members);
        $member = $output->members[0];
        self::assertSame(self::MEM_ID, $member->membershipId);
        self::assertSame(self::OP_ID, $member->operatorId);
        self::assertSame('operator@example.com', $member->email);
        self::assertSame('Example Operator', $member->displayName);
        self::assertSame(Role::Admin, $member->role);
    }

    public function testStaleMembershipDegradesToNullIdentity(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership(self::MEM_ID, self::OP_ID, self::ORG_ID, Role::Member, self::NOW, self::NOW));

        // No operator saved — the membership points at a removed operator.
        $output = (new ListOrganizationMembershipsUseCase($memberships, new InMemoryOperatorRepository()))
            ->execute(self::ORG_ID);

        self::assertCount(1, $output->members);
        self::assertSame(self::OP_ID, $output->members[0]->operatorId);
        self::assertNull($output->members[0]->email);
        self::assertNull($output->members[0]->displayName);
    }

    public function testReturnsEmptyForOrganizationWithNoMembers(): void
    {
        $output = (new ListOrganizationMembershipsUseCase(new InMemoryMembershipRepository(), new InMemoryOperatorRepository()))
            ->execute(self::ORG_ID);

        self::assertSame([], $output->members);
    }
}
