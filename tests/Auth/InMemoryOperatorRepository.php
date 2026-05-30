<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\OperatorRepositoryInterface;

final class InMemoryOperatorRepository implements OperatorRepositoryInterface
{
    /** @var array<string, Operator> */
    private array $byId = [];

    public function save(Operator $operator): void
    {
        $this->byId[$operator->id] = $operator;
    }

    public function findById(string $id): ?Operator
    {
        return $this->byId[$id] ?? null;
    }

    public function findByEmail(string $email): ?Operator
    {
        foreach ($this->byId as $operator) {
            if ($operator->email === $email) {
                return $operator;
            }
        }

        return null;
    }
}
