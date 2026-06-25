<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use DateTimeImmutable;
use NeNeSuite\Origin\GetOriginUpdatesUseCaseInterface;
use Throwable;

/**
 * Mirrors catalog version state from the Origin update signals (#256 / #258): `installedVersion`
 * from the sibling probe, `availableVersion` from the verified Origin latest. Defensive — an
 * unconfigured Origin (`available:false`) or any failure degrades to an empty map (no fabricated
 * data), so a catalog read is never broken by the mirror.
 */
final readonly class OriginCatalogAppVersionSource implements CatalogAppVersionSourceInterface
{
    public function __construct(
        private GetOriginUpdatesUseCaseInterface $updates,
    ) {
    }

    public function versions(DateTimeImmutable $now): array
    {
        try {
            $output = $this->updates->execute($now);
        } catch (Throwable) {
            return [];
        }

        if (!$output->available) {
            return [];
        }

        $versions = [];
        foreach ($output->updates as $signal) {
            $versions[$signal->product] = new CatalogAppVersions($signal->installedVersion, $signal->latestVersion);
        }

        return $versions;
    }
}
