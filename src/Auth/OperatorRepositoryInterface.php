<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface OperatorRepositoryInterface
{
    public function save(Operator $operator): void;

    public function findById(string $id): ?Operator;

    public function findByEmail(string $email): ?Operator;

    /**
     * All operators, oldest first (created_at ASC, id ASC).
     *
     * @return list<Operator>
     */
    public function all(): array;
}
