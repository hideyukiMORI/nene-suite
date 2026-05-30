<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOperatorRepository implements OperatorRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(Operator $operator): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO operators (id, email, password_hash, display_name, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
                SQL,
            [
                $operator->id,
                $operator->email,
                $operator->passwordHash,
                $operator->displayName,
                $operator->createdAt,
                $operator->updatedAt,
            ],
        );
    }

    public function findById(string $id): ?Operator
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM operators WHERE id = ?', [$id]));
    }

    public function findByEmail(string $email): ?Operator
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM operators WHERE email = ?', [$email]));
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function hydrate(?array $row): ?Operator
    {
        if ($row === null) {
            return null;
        }

        $displayName = $row['display_name'] ?? null;

        return new Operator(
            id: (string) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            displayName: $displayName === null ? null : (string) $displayName,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
