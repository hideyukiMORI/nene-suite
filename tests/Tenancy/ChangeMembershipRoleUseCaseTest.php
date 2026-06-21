<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\ChangeMembershipRoleInput;
use NeNeSuite\Tenancy\ChangeMembershipRoleUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipInvariantException;
use NeNeSuite\Tenancy\MembershipNotFoundException;
use NeNeSuite\Tenancy\MembershipValidationException;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class ChangeMembershipRoleUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const M1 = '01J8XRDM01000000000000000A';
    private const M2 = '01J8XRDM02000000000000000A';
    private const OP1 = '01J8XRDOP100000000000000ZA';
    private const OP2 = '01J8XRDOP200000000000000ZA';

    public function testChangesOrganizationRoleAndAuditsBeforeAfter(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Member));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($memberships, $recorder)
            ->execute(new ChangeMembershipRoleInput(self::M1, Role::Viewer));

        self::assertSame(Role::Viewer, $output->membership->role);
        $reloaded = $memberships->findById(self::M1);
        self::assertNotNull($reloaded);
        self::assertSame(Role::Viewer, $reloaded->role);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('membership.role_changed', $event->action);
        self::assertSame('membership', $event->entityType);
        self::assertSame(['role' => 'member'], $event->beforeJson);
        self::assertSame(['role' => 'viewer'], $event->afterJson);
    }

    public function testNoOpWhenRoleUnchanged(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($memberships, $recorder)
            ->execute(new ChangeMembershipRoleInput(self::M1, Role::Admin));

        self::assertSame(Role::Admin, $output->membership->role);
        self::assertCount(0, $recorder->commands, 'an unchanged role records nothing');
    }

    public function testThrowsNotFoundForUnknownMembership(): void
    {
        $this->expectException(MembershipNotFoundException::class);

        $this->useCase()->execute(new ChangeMembershipRoleInput(self::M1, Role::Viewer));
    }

    public function testRejectsGivingAnOrganizationMembershipThePlatformRole(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));

        $this->expectException(MembershipValidationException::class);

        $this->useCase($memberships)->execute(new ChangeMembershipRoleInput(self::M1, Role::Superadmin));
    }

    public function testRejectsGivingAPlatformMembershipAnOrganizationRole(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, null, Role::Superadmin));

        $this->expectException(MembershipValidationException::class);

        $this->useCase($memberships)->execute(new ChangeMembershipRoleInput(self::M1, Role::Member));
    }

    public function testRejectsDemotingTheLastAdminOfAnOrganization(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));
        $memberships->save($this->membership(self::M2, self::OP2, self::ORG_ID, Role::Member));

        $this->expectException(MembershipInvariantException::class);

        $this->useCase($memberships)->execute(new ChangeMembershipRoleInput(self::M1, Role::Member));
    }

    public function testAllowsDemotingAnAdminWhenAnotherAdminRemains(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($this->membership(self::M1, self::OP1, self::ORG_ID, Role::Admin));
        $memberships->save($this->membership(self::M2, self::OP2, self::ORG_ID, Role::Admin));

        $output = $this->useCase($memberships)
            ->execute(new ChangeMembershipRoleInput(self::M1, Role::Member));

        self::assertSame(Role::Member, $output->membership->role);
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
    ): ChangeMembershipRoleUseCase {
        return new ChangeMembershipRoleUseCase(
            transactions: new ImmediateTransactionManager(),
            memberships: new InMemoryMembershipRepositoryFactory($memberships ?? new InMemoryMembershipRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
