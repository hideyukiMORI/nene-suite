<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;

final class InMemoryOrganizationRepository implements OrganizationRepositoryInterface
{
    /** @var array<string, Organization> */
    private array $byId = [];

    public function save(Organization $organization): void
    {
        $this->byId[$organization->id] = $organization;
    }

    public function update(Organization $organization): void
    {
        $this->byId[$organization->id] = $organization;
    }

    public function findById(string $id): ?Organization
    {
        return $this->byId[$id] ?? null;
    }

    public function findByExternalId(string $externalId): ?Organization
    {
        foreach ($this->byId as $organization) {
            if ($organization->externalId === $externalId) {
                return $organization;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Organization
    {
        foreach ($this->byId as $organization) {
            if ($organization->slug === $slug) {
                return $organization;
            }
        }

        return null;
    }

    /**
     * @return list<Organization>
     */
    public function all(): array
    {
        return array_values($this->byId);
    }
}
