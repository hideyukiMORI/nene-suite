<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Validation\ValidationException;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\CreateOrganizationHandler;
use NeNeSuite\Tenancy\CreateOrganizationUseCase;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tenancy\OrganizationSlugConflictException;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class CreateOrganizationHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    public function testCreatesOrganizationForSuperadmin(): void
    {
        $recorder = new RecordingSuiteAuditRecorder();

        $response = $this->handler(new InMemoryOrganizationRepository(), $recorder)
            ->handle($this->request('POST', '/api/v1/organizations', $this->superadminToken(), ['name' => 'Acme KK', 'slug' => 'acme-kk']));

        self::assertSame(201, $response->getStatusCode());
        $body = $this->decode($response);
        self::assertSame('Acme KK', $body['name']);
        self::assertSame('acme-kk', $body['slug']);
        self::assertSame('active', $body['status']);
        self::assertArrayHasKey('externalId', $body);
        self::assertCount(1, $recorder->commands);
        self::assertSame('organization.created', $recorder->commands[0]->action);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler()->handle($this->request('POST', '/api/v1/organizations', $this->nonSuperadminToken(), ['name' => 'Acme', 'slug' => 'acme']));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler()->handle($this->request('POST', '/api/v1/organizations', null, ['name' => 'Acme', 'slug' => 'acme']));
    }

    public function testRejectsMissingFields(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler()->handle($this->request('POST', '/api/v1/organizations', $this->superadminToken(), ['ignored' => true]));
    }

    public function testRejectsDuplicateSlug(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $handler = $this->handler($organizations);
        $handler->handle($this->request('POST', '/api/v1/organizations', $this->superadminToken(), ['name' => 'Acme', 'slug' => 'acme']));

        $this->expectException(OrganizationSlugConflictException::class);

        $handler->handle($this->request('POST', '/api/v1/organizations', $this->superadminToken(), ['name' => 'Acme Two', 'slug' => 'acme']));
    }

    private function handler(
        ?InMemoryOrganizationRepository $organizations = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): CreateOrganizationHandler {
        return new CreateOrganizationHandler(
            $this->guard(),
            new CreateOrganizationUseCase(
                new ImmediateTransactionManager(),
                new InMemoryOrganizationRepositoryFactory($organizations ?? new InMemoryOrganizationRepository()),
                new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
                self::SUITE_ID,
            ),
            $this->jsonResponseFactory(),
            $this->requestIdHolder(),
        );
    }
}
