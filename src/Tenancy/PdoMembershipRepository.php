<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoMembershipRepository implements MembershipRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(Membership $membership): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO memberships (id, operator_id, organization_id, role, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
                SQL,
            [
                $membership->id,
                $membership->operatorId,
                $membership->organizationId,
                $membership->role->value,
                $membership->createdAt,
                $membership->updatedAt,
            ],
        );
    }

    public function findById(string $id): ?Membership
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM memberships WHERE id = ?', [$id]));
    }

    /**
     * @return list<Membership>
     */
    public function findByOperator(string $operatorId): array
    {
        return $this->hydrateAll(
            $this->query->fetchAll(
                'SELECT * FROM memberships WHERE operator_id = ? ORDER BY created_at ASC, id ASC',
                [$operatorId],
            ),
        );
    }

    public function findByOperatorAndOrganization(string $operatorId, ?string $organizationId): ?Membership
    {
        if ($organizationId === null) {
            return $this->hydrate($this->query->fetchOne(
                'SELECT * FROM memberships WHERE operator_id = ? AND organization_id IS NULL',
                [$operatorId],
            ));
        }

        return $this->hydrate($this->query->fetchOne(
            'SELECT * FROM memberships WHERE operator_id = ? AND organization_id = ?',
            [$operatorId, $organizationId],
        ));
    }

    /**
     * @return list<Membership>
     */
    public function findByOrganization(string $organizationId): array
    {
        return $this->hydrateAll(
            $this->query->fetchAll(
                'SELECT * FROM memberships WHERE organization_id = ? ORDER BY created_at ASC, id ASC',
                [$organizationId],
            ),
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<Membership>
     */
    private function hydrateAll(array $rows): array
    {
        $memberships = [];

        foreach ($rows as $row) {
            $membership = $this->hydrate($row);

            if ($membership !== null) {
                $memberships[] = $membership;
            }
        }

        return $memberships;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function hydrate(?array $row): ?Membership
    {
        if ($row === null) {
            return null;
        }

        $organizationId = $row['organization_id'] ?? null;

        return new Membership(
            id: (string) $row['id'],
            operatorId: (string) $row['operator_id'],
            organizationId: $organizationId === null ? null : (string) $organizationId,
            role: Role::from((string) $row['role']),
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
