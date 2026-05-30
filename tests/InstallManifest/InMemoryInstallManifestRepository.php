<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallManifest;

use NeNeSuite\InstallManifest\InstallManifest;
use NeNeSuite\InstallManifest\InstallManifestRepositoryInterface;

final class InMemoryInstallManifestRepository implements InstallManifestRepositoryInterface
{
    /** @var array<string, InstallManifest> */
    public array $manifests = [];

    public function save(InstallManifest $manifest): void
    {
        $this->manifests[$manifest->id] = $manifest;
    }

    public function findById(string $id): ?InstallManifest
    {
        return $this->manifests[$id] ?? null;
    }
}
