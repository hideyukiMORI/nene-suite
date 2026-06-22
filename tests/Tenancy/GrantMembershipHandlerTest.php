<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\GrantMembershipHandler;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipConflictException;
use NeNeSuite\Tenancy\MembershipValidationException;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Auth\InMemoryOperatorRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class GrantMembershipHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const ORG_EXT = '01J8XRDEXT0000000000000ZAB';
    private const OP_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';

    private function path(): string
    {
        return '/api/v1/organizations/' . self::ORG_ID . '/memberships';
    }

    public function testGrantsMembershipForSuperadmin(): void
    {
        $recorder = new RecordingSuiteAuditRecorder();

        $response = $this->handler(new InMemoryMembershipRepository(), $recorder)->handle(
            $this->request('POST', $this->path(), $this->superadminToken(), ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]),
        );

        self::assertSame(201, $response->getStatusCode());
        $body = $this->decode($response);
        self::assertSame(self::OP_ID, $body['operatorId']);
        self::assertSame(self::ORG_ID, $body['organizationId']);
        self::assertSame('admin', $body['role']);
        self::assertCount(1, $recorder->commands);
        self::assertSame('membership.granted', $recorder->commands[0]->action);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler()->handle($this->request('POST', $this->path(), $this->nonSuperadminToken(), ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler()->handle($this->request('POST', $this->path(), null, ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]));
    }

    public function testRejectsUnknownOrganizationWithNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->handler(organizations: new InMemoryOrganizationRepository())->handle(
            $this->request('POST', $this->path(), $this->superadminToken(), ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsSuperadminRoleWithValidation(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->handler()->handle($this->request('POST', $this->path(), $this->superadminToken(), ['operatorId' => self::OP_ID, 'role' => 'superadmin'], ['id' => self::ORG_ID]));
    }

    public function testRejectsMissingOperatorIdWithValidation(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->handler()->handle($this->request('POST', $this->path(), $this->superadminToken(), ['role' => 'admin'], ['id' => self::ORG_ID]));
    }

    public function testRejectsUnknownOperatorWithValidation(): void
    {
        $this->expectException(MembershipValidationException::class);

        $this->handler(operators: new InMemoryOperatorRepository())->handle(
            $this->request('POST', $this->path(), $this->superadminToken(), ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsDuplicateMembershipWithConflict(): void
    {
        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J8XRMEM00000000000000ZAB', self::OP_ID, self::ORG_ID, Role::Member, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $this->expectException(MembershipConflictException::class);

        $this->handler($memberships)->handle(
            $this->request('POST', $this->path(), $this->superadminToken(), ['operatorId' => self::OP_ID, 'role' => 'admin'], ['id' => self::ORG_ID]),
        );
    }

    private function handler(
        ?InMemoryMembershipRepository $memberships = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
        ?InMemoryOrganizationRepository $organizations = null,
        ?InMemoryOperatorRepository $operators = null,
    ): GrantMembershipHandler {
        return new GrantMembershipHandler(
            $this->guard(),
            new GrantMembershipUseCase(
                new ImmediateTransactionManager(),
                new InMemoryMembershipRepositoryFactory($memberships ?? new InMemoryMembershipRepository()),
                new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
                self::SUITE_ID,
            ),
            $organizations ?? $this->seededOrg(),
            $operators ?? $this->seededOperator(),
            $this->jsonResponseFactory(),
            $this->requestIdHolder(),
        );
    }

    private function seededOrg(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXT, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $organizations;
    }

    private function seededOperator(): InMemoryOperatorRepository
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP_ID, 'operator@example.com', 'hash', 'Example Operator', '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $operators;
    }
}
