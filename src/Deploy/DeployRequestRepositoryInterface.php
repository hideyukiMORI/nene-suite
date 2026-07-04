<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

interface DeployRequestRepositoryInterface
{
    public function insert(DeployRequest $request): void;

    public function update(DeployRequest $request): void;

    public function findById(string $id): ?DeployRequest;

    /**
     * Pending requests, oldest first (the agent consumes in FIFO order).
     *
     * @return list<DeployRequest>
     */
    public function findPending(): array;

    /**
     * Most recent requests, newest first, optionally filtered by status.
     *
     * @return list<DeployRequest>
     */
    public function findRecent(?DeployRequestStatus $status, int $limit): array;
}
