<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionStatus;

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

    public function findLatestCompleted(): ?InstallSession
    {
        $completed = null;
        foreach ($this->sessions as $session) {
            if ($session->status === InstallSessionStatus::Completed) {
                if ($completed === null || $session->completedAt > $completed->completedAt) {
                    $completed = $session;
                }
            }
        }

        return $completed;
    }
}
