<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use NeNeSuite\Deploy\DeployRequest;
use NeNeSuite\Deploy\DeployRequestRepositoryInterface;
use NeNeSuite\Deploy\DeployRequestStatus;

final class InMemoryDeployRequestRepository implements DeployRequestRepositoryInterface
{
    /** @var array<string, DeployRequest> */
    public array $rows = [];

    public function insert(DeployRequest $request): void
    {
        $this->rows[$request->id] = $request;
    }

    public function update(DeployRequest $request): void
    {
        $this->rows[$request->id] = $request;
    }

    public function findById(string $id): ?DeployRequest
    {
        return $this->rows[$id] ?? null;
    }

    public function findPending(): array
    {
        $pending = array_values(array_filter(
            $this->rows,
            static fn (DeployRequest $request): bool => $request->status === DeployRequestStatus::Pending,
        ));

        usort($pending, static fn (DeployRequest $a, DeployRequest $b): int => [$a->createdAt, $a->id] <=> [$b->createdAt, $b->id]);

        return $pending;
    }

    public function findRecent(?DeployRequestStatus $status, int $limit): array
    {
        $requests = array_values(array_filter(
            $this->rows,
            static fn (DeployRequest $request): bool => $status === null || $request->status === $status,
        ));

        usort($requests, static fn (DeployRequest $a, DeployRequest $b): int => [$b->createdAt, $b->id] <=> [$a->createdAt, $a->id]);

        return array_slice($requests, 0, max(1, $limit));
    }
}
