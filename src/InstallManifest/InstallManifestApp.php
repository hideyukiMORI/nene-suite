<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

/**
 * One entry of the manifest `apps[]` array (schema/install-manifest.schema.json):
 * the catalog id, public URL, and provisioned database name. No secrets.
 */
final readonly class InstallManifestApp
{
    public function __construct(
        public string $catalogId,
        public string $publicUrl,
        public string $databaseName,
    ) {
    }
}
