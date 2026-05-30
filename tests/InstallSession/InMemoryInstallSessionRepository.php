<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;

final class InMemoryInstallSessionRepository implements InstallSessionRepositoryInterface
{
    /** @var array<string, InstallSession> */
    private array $sessions = [];

    public function save(InstallSession $session): void
    {
        $this->sessions[$session->id] = $session;
    }

    public function update(InstallSession $session): void
    {
        $this->sessions[$session->id] = $session;
    }

    public function findById(string $id): ?InstallSession
    {
        return $this->sessions[$id] ?? null;
    }
}
