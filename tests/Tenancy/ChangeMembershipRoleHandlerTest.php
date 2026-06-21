<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ChangeMembershipRoleHandler;
use NeNeSuite\Tenancy\ChangeMembershipRoleUseCase;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipInvariantException;
use NeNeSuite\Tenancy\MembershipNotFoundException;
use NeNeSuite\Tenancy\MembershipValidationException;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class ChangeMembershipRoleHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const OP_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const MEM_ID = '01J8XRMEM00000000000000ZAB';
    private const UNKNOWN_ID = '01J8XRMEM11111111111111ZAB';

    private function path(string $id): string
    {
        return '/api/v1/memberships/' . $id;
    }

    public function testChangesRoleForSuperadmin(): void
    {
        $memberships = $this->seeded(Role::Member);
        $recorder = new RecordingSuiteAuditRecorder();

        $response = $this->handler($memberships, $recorder)->handle(
            $this->request('PATCH', $this->path(self::MEM_ID), $this->superadminToken(), ['role' => 'admin'], ['id' => self::MEM_ID]),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('admin', $this->decode($response)['role']);
        self::assertSame('membership.role_changed', $recorder->commands[0]->action);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler($this->seeded(Role::Member))->handle($this->request('PATCH', $this->path(self::MEM_ID), $this->nonSuperadminToken(), ['role' => 'admin'], ['id' => self::MEM_ID]));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler($this->seeded(Role::Member))->handle($this->request('PATCH', $this->path(self::MEM_ID), null, ['role' => 'admin'], ['id' => self::MEM_ID]));
    }

    public function testRejectsUnknownMembershipWithNotFound(): void
    {
        $this->expectException(MembershipNotFoundException::class);

        $this->handler(new InMemoryMembershipRepository())->handle(
            $this->request('PATCH', $this->path(self::UNKNOWN_ID), $this->superadminToken(), ['role' => 'admin'], ['id' => self::UNKNOWN_ID]),
        );
    }

    public function testRejectsInvalidRoleWithValidation(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->handler($this->seeded(Role::Member))->handle($this->request('PATCH', $this->path(self::MEM_ID), $this->superadminToken(), ['role' => 'superadmin'], ['id' => self::MEM_ID]));
    }

    public function testRejectsDemotingLastAdminWithInvariant(): void
    {
        $this->expectException(MembershipInvariantException::class);

        $this->handler($this->seeded(Role::Admin))->handle($this->request('PATCH', $this->path(self::MEM_ID), $this->superadminToken(), ['role' => 'member'], ['id' => self::MEM_ID]));
    }

    private function seeded(Role $role): InMemoryMembershipRepository
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership(self::MEM_ID, self::OP_ID, self::ORG_ID, $role, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $memberships;
    }

    private function handler(
        ?InMemoryMembershipRepository $memberships = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): ChangeMembershipRoleHandler {
        return new ChangeMembershipRoleHandler(
            $this->guard(),
            new ChangeMembershipRoleUseCase(
                new ImmediateTransactionManager(),
                new InMemoryMembershipRepositoryFactory($memberships ?? new InMemoryMembershipRepository()),
                new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
                self::SUITE_ID,
            ),
            $this->jsonResponseFactory(),
            $this->requestIdHolder(),
        );
    }
}
