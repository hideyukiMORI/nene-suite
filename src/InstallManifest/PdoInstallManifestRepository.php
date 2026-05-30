<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoInstallManifestRepository implements InstallManifestRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(InstallManifest $manifest): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO install_manifests (id, suite_id, content_json, content_hash, created_at)
                VALUES (?, ?, ?, ?, ?)
                SQL,
            [
                $manifest->id,
                $manifest->suiteId,
                json_encode($manifest->body, JSON_THROW_ON_ERROR),
                $manifest->contentHash,
                $manifest->createdAt,
            ],
        );
    }

    public function findById(string $id): ?InstallManifest
    {
        $row = $this->query->fetchOne(
            'SELECT * FROM install_manifests WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) ($row['content_json'] ?? ''), true);
        $body = is_array($decoded) ? $decoded : [];

        /** @var array<string, mixed> $body */

        return new InstallManifest(
            id: (string) $row['id'],
            suiteId: (string) $row['suite_id'],
            body: $body,
            contentHash: (string) $row['content_hash'],
            createdAt: (string) $row['created_at'],
        );
    }
}
