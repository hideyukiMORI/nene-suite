<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\ListOrganizationsHandler;
use NeNeSuite\Tenancy\ListOrganizationsUseCase;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use PHPUnit\Framework\TestCase;

final class ListOrganizationsHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    public function testListsOrganizationsForSuperadmin(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization('01J8XR0G7Q9V2H7K3N5M0B8TCA', '01J8XRDEXT0000000000000ZAB', 'Acme', 'acme', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $response = $this->handler($organizations)->handle($this->request('GET', '/api/v1/organizations', $this->superadminToken()));

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        self::assertArrayHasKey('organizations', $body);
        $list = $body['organizations'];
        self::assertIsArray($list);
        self::assertCount(1, $list);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler()->handle($this->request('GET', '/api/v1/organizations', $this->nonSuperadminToken()));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler()->handle($this->request('GET', '/api/v1/organizations', null));
    }

    private function handler(?InMemoryOrganizationRepository $organizations = null): ListOrganizationsHandler
    {
        return new ListOrganizationsHandler(
            $this->guard(),
            new ListOrganizationsUseCase($organizations ?? new InMemoryOrganizationRepository()),
            $this->jsonResponseFactory(),
        );
    }
}
