<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\CreateOrganizationInput;
use NeNeSuite\Tenancy\CreateOrganizationUseCase;
use NeNeSuite\Tenancy\OrganizationSlugConflictException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\OrganizationValidationException;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class CreateOrganizationUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    public function testCreatesActiveOrganizationWithDistinctIdsAndAudits(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($organizations, $recorder)
            ->execute(new CreateOrganizationInput('Acme KK', 'acme-kk'));

        $organization = $output->organization;
        self::assertSame('Acme KK', $organization->name);
        self::assertSame('acme-kk', $organization->slug);
        self::assertSame(OrganizationStatus::Active, $organization->status);
        self::assertNotEmpty($organization->id);
        self::assertNotEmpty($organization->externalId);
        self::assertNotSame($organization->id, $organization->externalId, 'external_id must be distinct from the internal id');

        $saved = $organizations->findBySlug('acme-kk');
        self::assertNotNull($saved);
        self::assertSame($organization->id, $saved->id);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('organization.created', $event->action);
        self::assertSame('organization', $event->entityType);
        self::assertSame($organization->id, $event->entityId);
        self::assertSame($organization->externalId, $event->orgExternalId);
        self::assertNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertSame($organization->externalId, $event->afterJson['externalId']);
        self::assertSame('acme-kk', $event->afterJson['slug']);
        self::assertSame('active', $event->afterJson['status']);
    }

    public function testTrimsName(): void
    {
        $output = $this->useCase()->execute(new CreateOrganizationInput('  Acme KK  ', 'acme-kk'));

        self::assertSame('Acme KK', $output->organization->name);
    }

    public function testThrowsConflictForDuplicateSlug(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $useCase = $this->useCase($organizations);
        $useCase->execute(new CreateOrganizationInput('Acme', 'acme'));

        $this->expectException(OrganizationSlugConflictException::class);

        $useCase->execute(new CreateOrganizationInput('Acme Two', 'acme'));
    }

    public function testThrowsValidationForEmptyName(): void
    {
        $this->expectException(OrganizationValidationException::class);

        $this->useCase()->execute(new CreateOrganizationInput('   ', 'valid-slug'));
    }

    public function testThrowsValidationForInvalidSlug(): void
    {
        $this->expectException(OrganizationValidationException::class);

        $this->useCase()->execute(new CreateOrganizationInput('Name', 'Not A Slug!'));
    }

    public function testUsesAndCanonicalisesAProvidedExternalId(): void
    {
        // A valid (lowercase) ULID seed is used and canonicalised to uppercase Crockford.
        $output = $this->useCase()->execute(new CreateOrganizationInput('Acme KK', 'acme-kk', null, '01j8xrdext0000000000000zab'));

        self::assertSame('01J8XRDEXT0000000000000ZAB', $output->organization->externalId);
    }

    public function testMintsAFreshExternalIdWhenTheSeedIsMalformed(): void
    {
        $output = $this->useCase()->execute(new CreateOrganizationInput('Acme KK', 'acme-kk', null, 'not-a-ulid'));

        self::assertNotSame('not-a-ulid', $output->organization->externalId);
        self::assertTrue(Ulid::isValid($output->organization->externalId));
    }

    public function testMintsAFreshExternalIdByDefault(): void
    {
        $first = $this->useCase()->execute(new CreateOrganizationInput('One', 'one'))->organization->externalId;
        $second = $this->useCase()->execute(new CreateOrganizationInput('Two', 'two'))->organization->externalId;

        self::assertTrue(Ulid::isValid($first));
        self::assertNotSame($first, $second);
    }

    private function useCase(
        ?InMemoryOrganizationRepository $organizations = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): CreateOrganizationUseCase {
        return new CreateOrganizationUseCase(
            transactions: new ImmediateTransactionManager(),
            organizations: new InMemoryOrganizationRepositoryFactory($organizations ?? new InMemoryOrganizationRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
