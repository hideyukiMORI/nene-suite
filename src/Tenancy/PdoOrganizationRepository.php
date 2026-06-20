<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOrganizationRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(Organization $organization): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO organizations (id, external_id, name, slug, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                $organization->id,
                $organization->externalId,
                $organization->name,
                $organization->slug,
                $organization->status->value,
                $organization->createdAt,
                $organization->updatedAt,
            ],
        );
    }

    public function findById(string $id): ?Organization
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM organizations WHERE id = ?', [$id]));
    }

    public function findByExternalId(string $externalId): ?Organization
    {
        return $this->hydrate(
            $this->query->fetchOne('SELECT * FROM organizations WHERE external_id = ?', [$externalId]),
        );
    }

    public function findBySlug(string $slug): ?Organization
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM organizations WHERE slug = ?', [$slug]));
    }

    /**
     * @return list<Organization>
     */
    public function all(): array
    {
        $organizations = [];

        foreach ($this->query->fetchAll('SELECT * FROM organizations ORDER BY created_at ASC, id ASC') as $row) {
            $organization = $this->hydrate($row);

            if ($organization !== null) {
                $organizations[] = $organization;
            }
        }

        return $organizations;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function hydrate(?array $row): ?Organization
    {
        if ($row === null) {
            return null;
        }

        return new Organization(
            id: (string) $row['id'],
            externalId: (string) $row['external_id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            status: OrganizationStatus::from((string) $row['status']),
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
