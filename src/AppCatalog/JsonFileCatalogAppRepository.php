<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use JsonException;

/**
 * Reads the authoritative catalog from catalog/apps.json on disk.
 *
 * Structural correctness (dependency DAG, uniqueness, enum membership) is
 * enforced separately by tools/validate-catalog.sh in CI; this repository only
 * parses and type-guards the fields it exposes.
 */
final readonly class JsonFileCatalogAppRepository implements CatalogAppRepositoryInterface
{
    public function __construct(
        private string $catalogPath,
    ) {
    }

    public function load(): Catalog
    {
        if (!is_file($this->catalogPath)) {
            throw new CatalogReadException("Catalog file not found: {$this->catalogPath}");
        }

        $raw = file_get_contents($this->catalogPath);

        if ($raw === false) {
            throw new CatalogReadException("Catalog file is not readable: {$this->catalogPath}");
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new CatalogReadException('Catalog file is not valid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new CatalogReadException('Catalog root must be an object.');
        }

        $version = $decoded['version'] ?? null;

        if (!is_int($version)) {
            throw new CatalogReadException('Catalog "version" must be an integer.');
        }

        $appsRaw = $decoded['apps'] ?? null;

        if (!is_array($appsRaw)) {
            throw new CatalogReadException('Catalog "apps" must be an array.');
        }

        $apps = [];

        foreach (array_values($appsRaw) as $index => $appRaw) {
            if (!is_array($appRaw)) {
                throw new CatalogReadException("Catalog app at index {$index} must be an object.");
            }

            $apps[] = $this->mapApp($appRaw, $index);
        }

        return new Catalog($version, $apps);
    }

    /**
     * @param array<array-key, mixed> $app
     */
    private function mapApp(array $app, int $index): CatalogApp
    {
        return new CatalogApp(
            id: $this->requireString($app, 'id', $index),
            name: $this->requireString($app, 'name', $index),
            repository: $this->optionalString($app, 'repository', $index),
            path: $this->requireString($app, 'path', $index),
            status: $this->requireString($app, 'status', $index),
            requires: $this->stringList($app, 'requires', $index),
            provides: $this->stringList($app, 'provides', $index),
            installEntry: $this->optionalString($app, 'install_entry', $index),
            databaseEnvPrefix: $this->optionalDatabaseEnvPrefix($app, $index),
        );
    }

    /**
     * @param array<array-key, mixed> $app
     */
    private function requireString(array $app, string $key, int $index): string
    {
        $value = $app[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new CatalogReadException("Catalog app at index {$index} has invalid \"{$key}\".");
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $app
     */
    private function optionalString(array $app, string $key, int $index): ?string
    {
        $value = $app[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new CatalogReadException("Catalog app at index {$index} has invalid \"{$key}\".");
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $app
     *
     * @return list<string>
     */
    private function stringList(array $app, string $key, int $index): array
    {
        $value = $app[$key] ?? [];

        if (!is_array($value)) {
            throw new CatalogReadException("Catalog app at index {$index} has invalid \"{$key}\".");
        }

        $list = [];

        foreach (array_values($value) as $item) {
            if (!is_string($item)) {
                throw new CatalogReadException("Catalog app at index {$index} has a non-string \"{$key}\" entry.");
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * @param array<array-key, mixed> $app
     */
    private function optionalDatabaseEnvPrefix(array $app, int $index): ?string
    {
        $database = $app['database'] ?? null;

        if ($database === null) {
            return null;
        }

        if (!is_array($database)) {
            throw new CatalogReadException("Catalog app at index {$index} has invalid \"database\".");
        }

        return $this->optionalString($database, 'env_prefix', $index);
    }
}
