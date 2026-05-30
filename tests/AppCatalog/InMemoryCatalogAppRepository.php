<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\AppCatalog\CatalogReadException;

final class InMemoryCatalogAppRepository implements CatalogAppRepositoryInterface
{
    public function __construct(
        private ?Catalog $catalog,
        private ?CatalogReadException $failure = null,
    ) {
    }

    public function load(): Catalog
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        if ($this->catalog === null) {
            throw new CatalogReadException('No catalog configured.');
        }

        return $this->catalog;
    }
}
