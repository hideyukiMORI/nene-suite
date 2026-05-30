<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

use stdClass;
use Symfony\Component\Uid\Ulid;

/**
 * Builds the install manifest (ADR 0010) from data available at completion.
 * `apps[]` carries the per-app public URL and provisioned database name for apps
 * with a configured URL; `app_versions` stays an empty object until version
 * pinning lands.
 */
final readonly class InstallManifestFactory
{
    /**
     * @param list<InstallManifestApp> $apps installed apps with a configured public URL
     */
    public function create(
        string $suiteId,
        string $orgExternalId,
        ?string $orgDisplayName,
        ?string $disclaimerAcceptedAt,
        array $apps = [],
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

        if ($apps !== []) {
            $body['apps'] = array_map(
                static fn (InstallManifestApp $app): array => [
                    'catalog_id' => $app->catalogId,
                    'public_url' => $app->publicUrl,
                    'database_name' => $app->databaseName,
                ],
                $apps,
            );
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
