<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

final readonly class ListCatalogAppsUseCase implements ListCatalogAppsUseCaseInterface
{
    public function __construct(
        private CatalogAppRepositoryInterface $catalog,
    ) {
    }

    public function execute(): ListCatalogAppsOutput
    {
        $catalog = $this->catalog->load();

        return new ListCatalogAppsOutput(
            version: $catalog->version,
            apps: $catalog->apps,
        );
    }
}
