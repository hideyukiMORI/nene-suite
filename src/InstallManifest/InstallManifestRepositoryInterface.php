<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

interface InstallManifestRepositoryInterface
{
    public function save(InstallManifest $manifest): void;

    public function findById(string $id): ?InstallManifest;
}
