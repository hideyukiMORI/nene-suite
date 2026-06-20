<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

interface OrganizationRepositoryInterface
{
    public function save(Organization $organization): void;

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
