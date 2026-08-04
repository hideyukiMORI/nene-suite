<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\UtcClock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/catalog/apps — operationId listCatalogApps.
 *
 * Maps the catalog snapshot to the camelCase CatalogAppList shape defined in
 * docs/openapi/openapi.yaml (install_entry → installEntry, database.env_prefix
 * → databaseEnvPrefix), enriched with the per-app version mirror (`installedVersion` /
 * `availableVersion`, ADR 0013 §4) — null when Origin is unconfigured / unknown.
 */
final readonly class ListCatalogAppsHandler
{
    public function __construct(
        private ListCatalogAppsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute($this->clock->now());
        $versions = $output->versions;

        return $this->response->create([
            'version' => $output->version,
            'apps' => array_map(
                static function (CatalogApp $app) use ($versions): array {
                    $appVersions = $versions[$app->id] ?? null;

                    return [
                        'id' => $app->id,
                        'name' => $app->name,
                        'repository' => $app->repository,
                        'path' => $app->path,
                        'status' => $app->status,
                        'requires' => $app->requires,
                        'provides' => $app->provides,
                        'installEntry' => $app->installEntry,
                        'databaseEnvPrefix' => $app->databaseEnvPrefix,
                        'installedVersion' => $appVersions?->installedVersion,
                        'availableVersion' => $appVersions?->availableVersion,
                    ];
                },
                $output->apps,
            ),
        ]);
    }
}
