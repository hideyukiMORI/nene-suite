<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\ListOrganizationsUseCase;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use PHPUnit\Framework\TestCase;

final class ListOrganizationsUseCaseTest extends TestCase
{
    public function testReturnsEmptyWhenNoOrganizations(): void
    {
        $output = (new ListOrganizationsUseCase(new InMemoryOrganizationRepository()))->execute();

        self::assertSame([], $output->organizations);
    }

    public function testReturnsEveryOrganization(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save($this->organization('01J8XR0G7Q9V2H7K3N5M0B8TCA', '01J8XRDEXT0000000000000ZAB', 'Acme', 'acme', OrganizationStatus::Active));
        $organizations->save($this->organization('01J8XR4ZS6Q9V2H7K3N5M0B8TC', '01J8XRDEXT0000000000000ZAC', 'Beta', 'beta', OrganizationStatus::Disabled));

        $output = (new ListOrganizationsUseCase($organizations))->execute();

        self::assertCount(2, $output->organizations);
        $slugs = array_map(static fn (Organization $organization): string => $organization->slug, $output->organizations);
        self::assertContains('acme', $slugs);
        self::assertContains('beta', $slugs);
    }

    private function organization(string $id, string $externalId, string $name, string $slug, OrganizationStatus $status): Organization
    {
        return new Organization($id, $externalId, $name, $slug, $status, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z');
    }
}
