<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

/**
 * Summary of a completed Tier B suite install. Safe to print to stdout — no secrets.
 */
final readonly class InstallerOutput
{
    /**
     * @param list<string> $provisionedDatabases database names created for each app
     */
    public function __construct(
        public string $installSessionId,
        public string $installManifestId,
        public string $operatorId,
        public array $provisionedDatabases,
        public string $envFilePath,
        public string $completedAt,
    ) {
    }
}
