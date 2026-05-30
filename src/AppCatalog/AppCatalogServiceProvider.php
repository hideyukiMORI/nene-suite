<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Http\RuntimeServiceProvider;
use Psr\Container\ContainerInterface;

final readonly class AppCatalogServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(AppDependencyResolver::class, static fn (ContainerInterface $container): AppDependencyResolver => new AppDependencyResolver())
            ->set(
                CatalogAppRepositoryInterface::class,
                static function (ContainerInterface $container): CatalogAppRepositoryInterface {
                    $projectRoot = $container->get(RuntimeServiceProvider::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new JsonFileCatalogAppRepository($projectRoot . '/catalog/apps.json');
                },
            )
            ->set(
                ListCatalogAppsUseCaseInterface::class,
                static function (ContainerInterface $container): ListCatalogAppsUseCaseInterface {
                    $repository = $container->get(CatalogAppRepositoryInterface::class);

                    if (!$repository instanceof CatalogAppRepositoryInterface) {
                        throw new LogicException('Catalog app repository service is invalid.');
                    }

                    return new ListCatalogAppsUseCase($repository);
                },
            )
            ->set(
                ListCatalogAppsHandler::class,
                static function (ContainerInterface $container): ListCatalogAppsHandler {
                    $useCase = $container->get(ListCatalogAppsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListCatalogAppsUseCaseInterface) {
                        throw new LogicException('ListCatalogApps use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListCatalogAppsHandler($useCase, $response);
                },
            )
            ->set(
                'nene-suite.route_registrar.app_catalog',
                static function (ContainerInterface $container): AppCatalogRouteRegistrar {
                    $listHandler = $container->get(ListCatalogAppsHandler::class);

                    if (!$listHandler instanceof ListCatalogAppsHandler) {
                        throw new LogicException('ListCatalogApps handler service is invalid.');
                    }

                    return new AppCatalogRouteRegistrar($listHandler);
                },
            );
    }
}
