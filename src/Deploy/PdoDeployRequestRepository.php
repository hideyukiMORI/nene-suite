<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoDeployRequestRepository implements DeployRequestRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function insert(DeployRequest $request): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO deploy_requests
                    (id, service, image_digest, status, requested_by, detail, created_at, updated_at, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                $request->id,
                $request->service,
                $request->imageDigest,
                $request->status->value,
                $request->requestedBy,
                $request->detail,
                $request->createdAt,
                $request->updatedAt,
                $request->completedAt,
            ],
        );
    }

    public function update(DeployRequest $request): void
    {
        $this->query->execute(
            <<<'SQL'
                UPDATE deploy_requests
                SET status = ?, detail = ?, updated_at = ?, completed_at = ?
                WHERE id = ?
                SQL,
            [
                $request->status->value,
                $request->detail,
                $request->updatedAt,
                $request->completedAt,
                $request->id,
            ],
        );
    }

    public function findById(string $id): ?DeployRequest
    {
        return $this->hydrate($this->query->fetchOne('SELECT * FROM deploy_requests WHERE id = ?', [$id]));
    }

    public function findPending(): array
    {
        $requests = [];

        foreach ($this->query->fetchAll(
            'SELECT * FROM deploy_requests WHERE status = ? ORDER BY created_at ASC, id ASC',
            [DeployRequestStatus::Pending->value],
        ) as $row) {
            $request = $this->hydrate($row);

            if ($request !== null) {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    public function findRecent(?DeployRequestStatus $status, int $limit): array
    {
        $sql = 'SELECT * FROM deploy_requests';
        $parameters = [];

        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $parameters[] = $status->value;
        }

        $sql .= sprintf(' ORDER BY created_at DESC, id DESC LIMIT %d', max(1, min(100, $limit)));

        $requests = [];

        foreach ($this->query->fetchAll($sql, $parameters) as $row) {
            $request = $this->hydrate($row);

            if ($request !== null) {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function hydrate(?array $row): ?DeployRequest
    {
        if ($row === null) {
            return null;
        }

        $status = DeployRequestStatus::tryFrom(is_string($row['status'] ?? null) ? $row['status'] : '');

        if (
            $status === null
            || !is_string($row['id'] ?? null)
            || !is_string($row['service'] ?? null)
            || !is_string($row['image_digest'] ?? null)
            || !is_string($row['created_at'] ?? null)
            || !is_string($row['updated_at'] ?? null)
        ) {
            return null;
        }

        return new DeployRequest(
            id: $row['id'],
            service: $row['service'],
            imageDigest: $row['image_digest'],
            status: $status,
            requestedBy: is_string($row['requested_by'] ?? null) ? $row['requested_by'] : null,
            detail: is_string($row['detail'] ?? null) ? $row['detail'] : null,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            completedAt: is_string($row['completed_at'] ?? null) ? $row['completed_at'] : null,
        );
    }
}
