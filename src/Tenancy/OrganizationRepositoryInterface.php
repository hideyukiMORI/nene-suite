<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface OrganizationRepositoryInterface
{
    public function save(Organization $organization): void;

    /**
     * Persists changes to an existing organization's mutable fields (name, slug,
     * status, updated_at). `external_id` and `created_at` are immutable.
     */
    public function update(Organization $organization): void;

    public function findById(string $id): ?Organization;

    public function findByExternalId(string $externalId): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    /**
     * All organizations, oldest first.
     *
     * @return list<Organization>
     */
    public function all(): array;
}
