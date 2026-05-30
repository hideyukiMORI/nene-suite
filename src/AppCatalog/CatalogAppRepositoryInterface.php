<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

interface CatalogAppRepositoryInterface
{
    /**
     * @throws CatalogReadException when the catalog source is missing or invalid.
     */
    public function load(): Catalog;
}
