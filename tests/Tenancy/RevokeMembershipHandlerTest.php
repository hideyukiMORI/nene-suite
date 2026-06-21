<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipInvariantException;
use NeNeSuite\Tenancy\MembershipNotFoundException;
use NeNeSuite\Tenancy\RevokeMembershipHandler;
use NeNeSuite\Tenancy\RevokeMembershipUseCase;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RevokeMembershipHandlerTest extends TestCase
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

    public function testRevokesMembershipForSuperadmin(): void
    {
        $recorder = new RecordingSuiteAuditRecorder();

        $response = $this->handler($this->seeded(Role::Member), $recorder)->handle(
            $this->request('DELETE', $this->path(self::MEM_ID), $this->superadminToken(), [], ['id' => self::MEM_ID]),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame('membership.revoked', $recorder->commands[0]->action);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler($this->seeded(Role::Member))->handle($this->request('DELETE', $this->path(self::MEM_ID), $this->nonSuperadminToken(), [], ['id' => self::MEM_ID]));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler($this->seeded(Role::Member))->handle($this->request('DELETE', $this->path(self::MEM_ID), null, [], ['id' => self::MEM_ID]));
    }

    public function testRejectsUnknownMembershipWithNotFound(): void
    {
        $this->expectException(MembershipNotFoundException::class);

        $this->handler(new InMemoryMembershipRepository())->handle(
            $this->request('DELETE', $this->path(self::UNKNOWN_ID), $this->superadminToken(), [], ['id' => self::UNKNOWN_ID]),
        );
    }

    public function testRejectsRevokingLastAdminWithInvariant(): void
    {
        $this->expectException(MembershipInvariantException::class);

        $this->handler($this->seeded(Role::Admin))->handle($this->request('DELETE', $this->path(self::MEM_ID), $this->superadminToken(), [], ['id' => self::MEM_ID]));
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
    ): RevokeMembershipHandler {
        return new RevokeMembershipHandler(
            $this->guard(),
            new RevokeMembershipUseCase(
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
