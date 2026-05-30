<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

/**
 * One entry from catalog/apps.json. Field names mirror the catalog JSON
 * (snake_case `install_entry`, `database.env_prefix`); HTTP presentation maps
 * them to camelCase in the handler (see docs/openapi/openapi.yaml).
 *
 * @phpstan-type CatalogAppShape array{
 *     id: string,
 *     name: string,
 *     repository: string|null,
 *     path: string,
 *     status: string,
 *     requires: list<string>,
 *     provides: list<string>,
 *     installEntry: string|null,
 *     databaseEnvPrefix: string|null
 * }
 */
final readonly class CatalogApp
{
    /**
     * @param list<string> $requires
     * @param list<string> $provides
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $repository,
        public string $path,
        public string $status,
        public array $requires,
        public array $provides,
        public ?string $installEntry,
        public ?string $databaseEnvPrefix,
    ) {
    }
}
