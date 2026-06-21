<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\GrantMembershipInput;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\MembershipConflictException;
use NeNeSuite\Tenancy\MembershipValidationException;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class GrantMembershipUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OPERATOR_ID = '01J8XRDOP000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';

    public function testGrantsPlatformSuperadminWithNullOrgAndAudits(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($memberships, $recorder)
            ->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin));

        $membership = $output->membership;
        self::assertSame(self::OPERATOR_ID, $membership->operatorId);
        self::assertNull($membership->organizationId);
        self::assertSame(Role::Superadmin, $membership->role);
        self::assertNotEmpty($membership->id);
        self::assertNotNull($memberships->findByOperatorAndOrganization(self::OPERATOR_ID, null));

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('membership.granted', $event->action);
        self::assertSame('membership', $event->entityType);
        self::assertSame($membership->id, $event->entityId);
        self::assertNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertNull($event->afterJson['organizationId']);
        self::assertSame('superadmin', $event->afterJson['role']);
    }

    public function testGrantsOrganizationScopedAdmin(): void
    {
        $memberships = new InMemoryMembershipRepository();

        $output = $this->useCase($memberships)
            ->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Admin, self::ORG_ID));

        self::assertSame(self::ORG_ID, $output->membership->organizationId);
        self::assertSame(Role::Admin, $output->membership->role);
    }

    public function testThrowsValidationWhenSuperadminIsScopedToOrganization(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->useCase()->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin, self::ORG_ID));
    }

    public function testThrowsValidationWhenOrganizationRoleHasNoOrganization(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->useCase()->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Member, null));
    }

    public function testThrowsConflictForDuplicateOrganizationMembership(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $useCase = $this->useCase($memberships);
        $useCase->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Admin, self::ORG_ID));

        $this->expectException(MembershipConflictException::class);

        $useCase->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Viewer, self::ORG_ID));
    }

    public function testThrowsConflictForSecondPlatformMembership(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $useCase = $this->useCase($memberships);
        $useCase->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin));

        $this->expectException(MembershipConflictException::class);

        $useCase->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin));
    }

    private function useCase(
        ?InMemoryMembershipRepository $memberships = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): GrantMembershipUseCase {
        return new GrantMembershipUseCase(
            transactions: new ImmediateTransactionManager(),
            memberships: new InMemoryMembershipRepositoryFactory($memberships ?? new InMemoryMembershipRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
