<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\OrganizationValidationException;
use NeNeSuite\Tenancy\RenameOrganizationInput;
use NeNeSuite\Tenancy\RenameOrganizationUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RenameOrganizationUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    public function testRenamesAndAuditsBeforeAfter(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->existingOrganization('Old Name'));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($organizations, $recorder)
            ->execute(new RenameOrganizationInput(self::ORG_ID, 'New Name'));

        self::assertSame('New Name', $output->organization->name);
        $reloaded = $organizations->findById(self::ORG_ID);
        self::assertNotNull($reloaded);
        self::assertSame('New Name', $reloaded->name);
        self::assertSame('old-slug', $reloaded->slug, 'slug is unchanged by a rename');

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('organization.renamed', $event->action);
        self::assertSame('organization', $event->entityType);
        self::assertSame(self::ORG_ID, $event->entityId);
        self::assertSame(self::ORG_EXTERNAL_ID, $event->orgExternalId);
        self::assertSame(['name' => 'Old Name'], $event->beforeJson);
        self::assertSame(['name' => 'New Name'], $event->afterJson);
    }

    public function testNoOpWhenNameUnchanged(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->existingOrganization('Same Name'));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($organizations, $recorder)
            ->execute(new RenameOrganizationInput(self::ORG_ID, 'Same Name'));

        self::assertSame('Same Name', $output->organization->name);
        self::assertCount(0, $recorder->commands, 'a no-op rename records nothing');
    }

    public function testThrowsNotFoundForUnknownOrganization(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->useCase()->execute(new RenameOrganizationInput(self::ORG_ID, 'New Name'));
    }

    public function testThrowsValidationForEmptyName(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->existingOrganization('Old Name'));

        $this->expectException(OrganizationValidationException::class);

        $this->useCase($organizations)->execute(new RenameOrganizationInput(self::ORG_ID, '   '));
    }

    private function existingOrganization(string $name): Organization
    {
        return new Organization(
            id: self::ORG_ID,
            externalId: self::ORG_EXTERNAL_ID,
            name: $name,
            slug: 'old-slug',
            status: OrganizationStatus::Active,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        );
    }

    private function useCase(
        ?InMemoryOrganizationRepository $organizations = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): RenameOrganizationUseCase {
        return new RenameOrganizationUseCase(
            transactions: new ImmediateTransactionManager(),
            organizations: new InMemoryOrganizationRepositoryFactory($organizations ?? new InMemoryOrganizationRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
