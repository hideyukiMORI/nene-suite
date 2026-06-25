<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

/**
 * The version mirror for one catalog app (ADR 0013 §4): the currently installed version and the
 * latest available version, both mirrored from Origin via the update signals — never originated
 * here. Either is null when unknown (Origin unconfigured, app not installed, or no version reported).
 */
final readonly class CatalogAppVersions
{
    public function __construct(
        public ?string $installedVersion,
        public ?string $availableVersion,
    ) {
    }
}
