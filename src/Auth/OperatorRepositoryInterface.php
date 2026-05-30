<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface OperatorRepositoryInterface
{
    public function save(Operator $operator): void;

    public function findById(string $id): ?Operator;

    public function findByEmail(string $email): ?Operator;
}
