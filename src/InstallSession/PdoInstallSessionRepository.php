<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;

final readonly class PdoInstallSessionRepository implements InstallSessionRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(InstallSession $session): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO install_sessions (
                    id, suite_id, status, tier, catalog_revision, selected_apps_json,
                    database_targets_json, disclaimer_accepted, disclaimer_accepted_at, org_external_id,
                    org_display_name, install_manifest_id, failure_code,
                    created_at, updated_at, completed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                $session->id,
                $session->suiteId,
                $session->status->value,
                $session->tier->value,
                $session->catalogRevision,
                json_encode($session->selectedApps, JSON_THROW_ON_ERROR),
                $this->encodeDatabaseTargets($session->databaseTargets),
                $session->disclaimerAccepted ? 1 : 0,
                $session->disclaimerAcceptedAt,
                $session->orgExternalId,
                $session->orgDisplayName,
                $session->installManifestId,
                $session->failureCode,
                $session->createdAt,
                $session->updatedAt,
                $session->completedAt,
            ],
        );
    }

    public function update(InstallSession $session): void
    {
        $this->query->execute(
            <<<'SQL'
                UPDATE install_sessions SET
                    status = ?, tier = ?, catalog_revision = ?, selected_apps_json = ?,
                    database_targets_json = ?, disclaimer_accepted = ?, disclaimer_accepted_at = ?, org_external_id = ?,
                    org_display_name = ?, install_manifest_id = ?, failure_code = ?,
                    updated_at = ?, completed_at = ?
                WHERE id = ?
                SQL,
            [
                $session->status->value,
                $session->tier->value,
                $session->catalogRevision,
                json_encode($session->selectedApps, JSON_THROW_ON_ERROR),
                $this->encodeDatabaseTargets($session->databaseTargets),
                $session->disclaimerAccepted ? 1 : 0,
                $session->disclaimerAcceptedAt,
                $session->orgExternalId,
                $session->orgDisplayName,
                $session->installManifestId,
                $session->failureCode,
                $session->updatedAt,
                $session->completedAt,
                $session->id,
            ],
        );
    }

    public function findById(string $id): ?InstallSession
    {
        $row = $this->query->fetchOne(
            'SELECT * FROM install_sessions WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findLatestCompleted(): ?InstallSession
    {
        $row = $this->query->fetchOne(
            "SELECT * FROM install_sessions WHERE status = 'completed' ORDER BY completed_at DESC, id DESC LIMIT 1",
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): InstallSession
    {
        return new InstallSession(
            id: (string) $row['id'],
            suiteId: (string) $row['suite_id'],
            status: InstallSessionStatus::from((string) $row['status']),
            tier: InstallTier::from((string) $row['tier']),
            catalogRevision: (int) $row['catalog_revision'],
            selectedApps: $this->decodeSelectedApps($row['selected_apps_json'] ?? null),
            disclaimerAccepted: self::toBool($row['disclaimer_accepted']),
            disclaimerAcceptedAt: $this->nullableString($row['disclaimer_accepted_at'] ?? null),
            orgExternalId: $this->nullableString($row['org_external_id'] ?? null),
            orgDisplayName: $this->nullableString($row['org_display_name'] ?? null),
            installManifestId: $this->nullableString($row['install_manifest_id'] ?? null),
            failureCode: $this->nullableString($row['failure_code'] ?? null),
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            completedAt: $this->nullableString($row['completed_at'] ?? null),
            databaseTargets: $this->decodeDatabaseTargets($row['database_targets_json'] ?? null),
        );
    }

    /**
     * @return list<string>
     */
    private function decodeSelectedApps(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        $apps = [];

        foreach ($decoded as $app) {
            if (is_string($app)) {
                $apps[] = $app;
            }
        }

        return $apps;
    }

    /**
     * @param list<AppDatabaseTargetSelection> $targets
     */
    private function encodeDatabaseTargets(array $targets): string
    {
        $rows = [];

        foreach ($targets as $target) {
            $row = [
                'catalog_id' => $target->catalogId,
                'mode'       => $target->mode->value,
            ];

            if ($target->server !== null) {
                $row['server'] = $target->server;
            }

            if ($target->name !== null) {
                $row['name'] = $target->name;
            }

            $rows[] = $row;
        }

        return json_encode($rows, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<AppDatabaseTargetSelection>
     */
    private function decodeDatabaseTargets(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        $targets = [];

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $catalogId = $entry['catalog_id'] ?? null;
            $mode = is_string($entry['mode'] ?? null) ? DatabaseTargetMode::tryFrom($entry['mode']) : null;

            if (!is_string($catalogId) || $mode === null) {
                continue;
            }

            $server = $entry['server'] ?? null;
            $name = $entry['name'] ?? null;

            $targets[] = new AppDatabaseTargetSelection(
                $catalogId,
                $mode,
                is_string($server) ? $server : null,
                is_string($name) ? $name : null,
            );
        }

        return $targets;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * Engine-agnostic boolean read. MySQL/SQLite store 1/0 (read back as int or '1'/'0'),
     * while PostgreSQL returns boolean columns as 't'/'f'. A naive `(bool)` cast would turn
     * the string 'f' into true, so normalize the known truthy representations explicitly.
     */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 't', 'true'], true);
    }
}
