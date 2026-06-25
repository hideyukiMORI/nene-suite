<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use DateTimeImmutable;

final readonly class ListCatalogAppsUseCase implements ListCatalogAppsUseCaseInterface
{
    public function __construct(
        private CatalogAppRepositoryInterface $catalog,
        private CatalogAppVersionSourceInterface $versions,
    ) {
    }

    public function execute(DateTimeImmutable $now): ListCatalogAppsOutput
    {
        // Load the catalog first: a catalog read failure is the real error and must propagate. The
        // version mirror is best-effort (the source degrades to an empty map), so it never breaks
        // the catalog read.
        $catalog = $this->catalog->load();

        return new ListCatalogAppsOutput(
            version: $catalog->version,
            apps: $catalog->apps,
            versions: $this->versions->versions($now),
        );
    }
}
