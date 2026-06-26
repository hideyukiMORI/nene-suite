<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

use NeNeSuite\DatabaseProvision\DatabaseTargetMode;

/**
 * One entry of the manifest `apps[]` array (schema/install-manifest.schema.json):
 * the catalog id, public URL, and the app's database target (ADR 0021) — database
 * name, mode, and non-secret server label. No secrets.
 *
 * `mode` defaults to `Provision` and `server` to null; the factory omits both from the
 * serialized entry when they hold those defaults, so a provision-on-suite-server app is
 * byte-identical to the historical `{catalog_id, public_url, database_name}` shape.
 */
final readonly class InstallManifestApp
{
    public function __construct(
        public string $catalogId,
        public string $publicUrl,
        public string $databaseName,
        public DatabaseTargetMode $mode = DatabaseTargetMode::Provision,
        public ?string $server = null,
    ) {
    }
}
