<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use DateTimeImmutable;

interface ListCatalogAppsUseCaseInterface
{
    /**
     * @throws CatalogReadException when the catalog source is missing or invalid.
     */
    public function execute(DateTimeImmutable $now): ListCatalogAppsOutput;
}
