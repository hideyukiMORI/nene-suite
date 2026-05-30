<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Database\DatabaseQueryExecutorInterface;

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
                    disclaimer_accepted, disclaimer_accepted_at, org_external_id,
                    org_display_name, install_manifest_id, failure_code,
                    created_at, updated_at, completed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                $session->id,
                $session->suiteId,
                $session->status->value,
                $session->tier->value,
                $session->catalogRevision,
                json_encode($session->selectedApps, JSON_THROW_ON_ERROR),
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
                    disclaimer_accepted = ?, disclaimer_accepted_at = ?, org_external_id = ?,
                    org_display_name = ?, install_manifest_id = ?, failure_code = ?,
                    updated_at = ?, completed_at = ?
                WHERE id = ?
                SQL,
            [
                $session->status->value,
                $session->tier->value,
                $session->catalogRevision,
                json_encode($session->selectedApps, JSON_THROW_ON_ERROR),
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

        return new InstallSession(
            id: (string) $row['id'],
            suiteId: (string) $row['suite_id'],
            status: InstallSessionStatus::from((string) $row['status']),
            tier: InstallTier::from((string) $row['tier']),
            catalogRevision: (int) $row['catalog_revision'],
            selectedApps: $this->decodeSelectedApps($row['selected_apps_json'] ?? null),
            disclaimerAccepted: (bool) $row['disclaimer_accepted'],
            disclaimerAcceptedAt: $this->nullableString($row['disclaimer_accepted_at'] ?? null),
            orgExternalId: $this->nullableString($row['org_external_id'] ?? null),
            orgDisplayName: $this->nullableString($row['org_display_name'] ?? null),
            installManifestId: $this->nullableString($row['install_manifest_id'] ?? null),
            failureCode: $this->nullableString($row['failure_code'] ?? null),
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            completedAt: $this->nullableString($row['completed_at'] ?? null),
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

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
