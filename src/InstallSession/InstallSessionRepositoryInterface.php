<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

interface InstallSessionRepositoryInterface
{
    public function save(InstallSession $session): void;

    public function findById(string $id): ?InstallSession;
}
