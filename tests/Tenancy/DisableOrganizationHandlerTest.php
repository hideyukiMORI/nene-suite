<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\DisableOrganizationHandler;
use NeNeSuite\Tenancy\DisableOrganizationUseCase;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class DisableOrganizationHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_EXT = '01J8XRDEXT0000000000000ZAB';
    private const UNKNOWN_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testDisablesForSuperadmin(): void
    {
        $response = $this->handler($this->seeded())->handle(
            $this->request('POST', '/api/v1/organizations/' . self::ORG_ID . '/disable', $this->superadminToken(), [], ['id' => self::ORG_ID]),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('disabled', $this->decode($response)['status']);
    }

    public function testIsIdempotentWhenAlreadyDisabled(): void
    {
        $handler = $this->handler($this->seeded());
        $handler->handle($this->request('POST', '/api/v1/organizations/' . self::ORG_ID . '/disable', $this->superadminToken(), [], ['id' => self::ORG_ID]));

        $response = $handler->handle($this->request('POST', '/api/v1/organizations/' . self::ORG_ID . '/disable', $this->superadminToken(), [], ['id' => self::ORG_ID]));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('disabled', $this->decode($response)['status']);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler($this->seeded())->handle(
            $this->request('POST', '/api/v1/organizations/' . self::ORG_ID . '/disable', $this->nonSuperadminToken(), [], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler($this->seeded())->handle(
            $this->request('POST', '/api/v1/organizations/' . self::ORG_ID . '/disable', null, [], ['id' => self::ORG_ID]),
        );
    }

    public function testRejectsUnknownIdWithNotFound(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->handler()->handle(
            $this->request('POST', '/api/v1/organizations/' . self::UNKNOWN_ID . '/disable', $this->superadminToken(), [], ['id' => self::UNKNOWN_ID]),
        );
    }

    private function seeded(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXT, 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        return $organizations;
    }

    private function handler(?InMemoryOrganizationRepository $organizations = null): DisableOrganizationHandler
    {
        return new DisableOrganizationHandler(
            $this->guard(),
            new DisableOrganizationUseCase(
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
