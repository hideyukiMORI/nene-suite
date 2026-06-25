<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Throwable;

/**
 * Control-DB store for the last-known installed version per product (#255). Upsert with
 * last-write-wins semantics: {@see record()} overwrites the stored version with the latest probed
 * value (a version may move up or down), and a lost first-insert race falls through to the UPDATE.
 * Best-effort, no transaction needed — this is a probe cache, not an audited mutation (same posture
 * as the gen watermark store).
 */
final readonly class PdoInstalledVersionRepository implements InstalledVersionRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function current(string $catalogId): ?string
    {
        $row = $this->query->fetchOne('SELECT version FROM installed_app_versions WHERE catalog_id = ?', [$catalogId]);

        if ($row === null) {
            return null;
        }

        $version = $row['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : null;
    }

    public function record(string $catalogId, string $version, string $now): void
    {
        $row = $this->query->fetchOne('SELECT version FROM installed_app_versions WHERE catalog_id = ?', [$catalogId]);

        if ($row === null) {
            try {
                $this->query->execute(
                    'INSERT INTO installed_app_versions (catalog_id, version, checked_at) VALUES (?, ?, ?)',
                    [$catalogId, $version, $now],
                );

                return;
            } catch (Throwable) {
                // Lost a concurrent first-insert race — fall through to the UPDATE below.
            }
        }

        $this->query->execute(
            'UPDATE installed_app_versions SET version = ?, checked_at = ? WHERE catalog_id = ?',
            [$version, $now, $catalogId],
        );
    }
}
