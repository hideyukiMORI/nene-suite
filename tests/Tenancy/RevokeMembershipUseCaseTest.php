<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipInvariantException;
use NeNeSuite\Tenancy\MembershipNotFoundException;
use NeNeSuite\Tenancy\RevokeMembershipInput;
use NeNeSuite\Tenancy\RevokeMembershipUseCase;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RevokeMembershipUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const M1 = '01J8XRDM01000000000000000A';
    private const M2 = '01J8XRDM02000000000000000A';
    private const OP1 = '01J8XRDOP100000000000000ZA';
    private const OP2 = '01J8XRDOP200000000000000ZA';

    public function testRevokesMemberAndRecordsBeforeSnapshot(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Member));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($memberships, $recorder)
            ->execute(new RevokeMembershipInput(self::M1));

        self::assertSame(self::M1, $output->membership->id);
        self::assertNull($memberships->findById(self::M1), 'the membership row is removed');

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('membership.revoked', $event->action);
        self::assertSame('membership', $event->entityType);
        self::assertSame(self::M1, $event->entityId);
        self::assertNotNull($event->beforeJson);
        self::assertSame('member', $event->beforeJson['role']);
        self::assertNull($event->afterJson);
    }

    public function testThrowsNotFoundForUnknownMembership(): void
    {
        $this->expectException(MembershipNotFoundException::class);

        $this->useCase()->execute(new RevokeMembershipInput(self::M1));
    }

    public function testRejectsRevokingTheLastPlatformSuperadmin(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, null, Role::Superadmin));

        $this->expectException(MembershipInvariantException::class);

        $this->useCase($memberships)->execute(new RevokeMembershipInput(self::M1));
    }

    public function testRevokesSuperadminWhenAnotherPlatformSuperadminRemains(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, null, Role::Superadmin));
        $memberships->save($this->membership(self::M2, self::OP2, null, Role::Superadmin));

        $this->useCase($memberships)->execute(new RevokeMembershipInput(self::M1));

        self::assertNull($memberships->findById(self::M1));
        self::assertNotNull($memberships->findById(self::M2));
    }

    public function testRejectsRevokingTheLastAdminOfAnOrganization(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));
        $memberships->save($this->membership(self::M2, self::OP2, self::ORG_ID, Role::Member));

        $this->expectException(MembershipInvariantException::class);

        $this->useCase($memberships)->execute(new RevokeMembershipInput(self::M1));
    }

    public function testRevokesAdminWhenAnotherAdminRemains(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));
        $memberships->save($this->membership(self::M2, self::OP2, self::ORG_ID, Role::Admin));

        $this->useCase($memberships)->execute(new RevokeMembershipInput(self::M1));

        self::assertNull($memberships->findById(self::M1));
        self::assertNotNull($memberships->findById(self::M2));
    }

    private function membership(string $id, string $operatorId, ?string $organizationId, Role $role): Membership
    {
        return new Membership(
            id: $id,
            operatorId: $operatorId,
            organizationId: $organizationId,
            role: $role,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        );
    }

    private function useCase(
        ?InMemoryMembershipRepository $memberships = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): RevokeMembershipUseCase {
        return new RevokeMembershipUseCase(
            transactions: new ImmediateTransactionManager(),
            memberships: new InMemoryMembershipRepositoryFactory($memberships ?? new InMemoryMembershipRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
