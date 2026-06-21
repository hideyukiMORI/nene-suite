<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\DisableOrganizationInput;
use NeNeSuite\Tenancy\DisableOrganizationUseCase;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class DisableOrganizationUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    public function testDisablesActiveOrganizationAndAudits(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->organization(OrganizationStatus::Active));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($organizations, $recorder)
            ->execute(new DisableOrganizationInput(self::ORG_ID));

        self::assertSame(OrganizationStatus::Disabled, $output->organization->status);
        $reloaded = $organizations->findById(self::ORG_ID);
        self::assertNotNull($reloaded);
        self::assertSame(OrganizationStatus::Disabled, $reloaded->status);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('organization.disabled', $event->action);
        self::assertSame('organization', $event->entityType);
        self::assertSame(self::ORG_ID, $event->entityId);
        self::assertSame(self::ORG_EXTERNAL_ID, $event->orgExternalId);
        self::assertNotNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertSame('active', $event->beforeJson['status']);
        self::assertSame('disabled', $event->afterJson['status']);
    }

    public function testIsIdempotentWhenAlreadyDisabled(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->organization(OrganizationStatus::Disabled));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($organizations, $recorder)
            ->execute(new DisableOrganizationInput(self::ORG_ID));

        self::assertSame(OrganizationStatus::Disabled, $output->organization->status);
        self::assertCount(0, $recorder->commands, 'disabling an already-disabled org records nothing');
    }

    public function testThrowsNotFoundForUnknownOrganization(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->useCase()->execute(new DisableOrganizationInput(self::ORG_ID));
    }

    private function organization(OrganizationStatus $status): Organization
    {
        return new Organization(
            id: self::ORG_ID,
            externalId: self::ORG_EXTERNAL_ID,
            name: 'Acme KK',
            slug: 'acme-kk',
            status: $status,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        );
    }

    private function useCase(
        ?InMemoryOrganizationRepository $organizations = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): DisableOrganizationUseCase {
        return new DisableOrganizationUseCase(
            transactions: new ImmediateTransactionManager(),
            organizations: new InMemoryOrganizationRepositoryFactory($organizations ?? new InMemoryOrganizationRepository()),
            audit: new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            suiteId: self::SUITE_ID,
        );
    }
}
