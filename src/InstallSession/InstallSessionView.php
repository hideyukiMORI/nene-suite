<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

/**
 * Single source for the camelCase InstallSession representation used both for
 * HTTP responses (docs/openapi/openapi.yaml InstallSession schema) and for audit
 * before/after snapshots, so the operator-facing view and the audit view never
 * diverge (audit-trail.md §5).
 */
final readonly class InstallSessionView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(InstallSession $session): array
    {
        return [
            'id' => $session->id,
            'suiteId' => $session->suiteId,
            'status' => $session->status->value,
            'tier' => $session->tier->value,
            'catalogRevision' => $session->catalogRevision,
            'selectedApps' => $session->selectedApps,
            'databaseTargets' => array_map(
                static fn (AppDatabaseTargetSelection $selection): array => [
                    'catalogId' => $selection->catalogId,
                    'mode' => $selection->mode->value,
                    'server' => $selection->server,
                    'name' => $selection->name,
                ],
                $session->databaseTargets,
            ),
            'disclaimerAccepted' => $session->disclaimerAccepted,
            'disclaimerAcceptedAt' => $session->disclaimerAcceptedAt,
            'orgExternalId' => $session->orgExternalId,
            'orgDisplayName' => $session->orgDisplayName,
            'installManifestId' => $session->installManifestId,
            'failureCode' => $session->failureCode,
            'createdAt' => $session->createdAt,
            'updatedAt' => $session->updatedAt,
            'completedAt' => $session->completedAt,
        ];
    }
}
