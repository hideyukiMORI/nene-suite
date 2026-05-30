<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/catalog/apps — operationId listCatalogApps.
 *
 * Maps the catalog snapshot to the camelCase CatalogAppList shape defined in
 * docs/openapi/openapi.yaml (install_entry → installEntry, database.env_prefix
 * → databaseEnvPrefix).
 */
final readonly class ListCatalogAppsHandler
{
    public function __construct(
        private ListCatalogAppsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'version' => $output->version,
            'apps' => array_map(
                static fn (CatalogApp $app): array => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'repository' => $app->repository,
                    'path' => $app->path,
                    'status' => $app->status,
                    'requires' => $app->requires,
                    'provides' => $app->provides,
                    'installEntry' => $app->installEntry,
                    'databaseEnvPrefix' => $app->databaseEnvPrefix,
                ],
                $output->apps,
            ),
        ]);
    }
}
