<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

final readonly class InstalledApp
{
    public function __construct(
        public string $catalogId,
        public string $name,
        public string $publicUrl,
        public ?string $databaseName,
        public SsotRole $ssotRole,
    ) {
    }
}
