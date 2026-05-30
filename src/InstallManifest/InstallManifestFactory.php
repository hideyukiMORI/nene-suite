<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

use stdClass;
use Symfony\Component\Uid\Ulid;

/**
 * Builds the Phase 1 minimal install manifest (ADR 0010) from data available at
 * completion. Per-app provisioning details (`apps[]`, pinned `app_versions`) are
 * added by later provisioning slices; `app_versions` is an empty object until then.
 */
final readonly class InstallManifestFactory
{
    public function create(
        string $suiteId,
        string $orgExternalId,
        ?string $orgDisplayName,
        ?string $disclaimerAcceptedAt,
    ): InstallManifest {
        $installedAt = gmdate('Y-m-d\TH:i:s\Z');

        $body = [
            'suite_id' => $suiteId,
            'installed_at' => $installedAt,
            'app_versions' => new stdClass(),
            'org_external_id' => $orgExternalId,
            'enabled_integrations' => [],
        ];

        if ($orgDisplayName !== null) {
            $body['org_display_name'] = $orgDisplayName;
        }

        if ($disclaimerAcceptedAt !== null) {
            $body['disclaimer_accepted_at'] = $disclaimerAcceptedAt;
        }

        $contentHash = hash('sha256', json_encode($body, JSON_THROW_ON_ERROR));

        return new InstallManifest(
            id: (string) new Ulid(),
            suiteId: $suiteId,
            body: $body,
            contentHash: $contentHash,
            createdAt: $installedAt,
        );
    }
}
