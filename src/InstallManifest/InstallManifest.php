<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

/**
 * A point-in-time install snapshot persisted in the nene_suite control DB
 * (ADR 0010). `body` is the schema-shaped (snake_case) manifest matching
 * schema/install-manifest.schema.json; it contains no secrets.
 */
final readonly class InstallManifest
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        public string $id,
        public string $suiteId,
        public array $body,
        public string $contentHash,
        public string $createdAt,
    ) {
    }
}
