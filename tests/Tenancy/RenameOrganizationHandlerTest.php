<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Validation\ValidationException;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\RenameOrganizationHandler;
use NeNeSuite\Tenancy\RenameOrganizationUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RenameOrganizationHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_EXT = '01J8XRDEXT0000000000000ZAB';
    private const UNKNOWN_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testRenamesForSuperadmin(): void
    {
        $response = $this->handler($this->seeded())->handle(
            $this->request('PATCH', '/api/v1/organizations/' . self::ORG_ID, $this->superadminToken(), ['name' => 'Acme Kabushiki Kaisha'], ['id' => self::ORG_ID]),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Acme Kabushiki Kaisha', $this->decode($response)['name']);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler($this->seeded())->handle(
            $this->request('PATCH', '/api/v1/organizations/' . self::ORG_ID, $this->nonSuperadminToken(), ['name' => 'X'], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler($this->seeded())->handle(
            $this->request('PATCH', '/api/v1/organizations/' . self::ORG_ID, null, ['name' => 'X'], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsMalformedIdWithNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->handler()->handle(
            $this->request('PATCH', '/api/v1/organizations/not-a-ulid', $this->superadminToken(), ['name' => 'X'], ['id' => 'not-a-ulid']),
        );
    }

    public function testRejectsUnknownIdWithNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->handler()->handle(
            $this->request('PATCH', '/api/v1/organizations/' . self::UNKNOWN_ID, $this->superadminToken(), ['name' => 'X'], ['id' => self::UNKNOWN_ID]),
        );
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler($this->seeded())->handle(
            $this->request('PATCH', '/api/v1/organizations/' . self::ORG_ID, $this->superadminToken(), ['name' => '   '], ['id' => self::ORG_ID]),
        );
    }

    private function seeded(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXT, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $organizations;
    }

    private function handler(?InMemoryOrganizationRepository $organizations = null): RenameOrganizationHandler
    {
        return new RenameOrganizationHandler(
            $this->guard(),
            new RenameOrganizationUseCase(
                new ImmediateTransactionManager(),
                new InMemoryOrganizationRepositoryFactory($organizations ?? new InMemoryOrganizationRepository()),
                new RecordingSuiteAuditRecorderFactory(new RecordingSuiteAuditRecorder()),
                self::SUITE_ID,
            ),
            $this->jsonResponseFactory(),
            $this->requestIdHolder(),
        );
    }
}
