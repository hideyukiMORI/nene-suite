<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteEnv\SuiteAppUrlReaderInterface;
use Psr\Container\ContainerInterface;

final readonly class InstalledAppsServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ListInstalledAppsUseCaseInterface::class,
                static function (ContainerInterface $container): ListInstalledAppsUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $catalog = $container->get(CatalogAppRepositoryInterface::class);
                    $urls = $container->get(SuiteAppUrlReaderInterface::class);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$catalog instanceof CatalogAppRepositoryInterface) {
                        throw new LogicException('Catalog app repository service is invalid.');
                    }

                    if (!$urls instanceof SuiteAppUrlReaderInterface) {
                        throw new LogicException('Suite app URL reader service is invalid.');
                    }

                    return new ListInstalledAppsUseCase($sessions, $catalog, $urls);
                },
            )
            ->set(
                ListInstalledAppsHandler::class,
                static function (ContainerInterface $container): ListInstalledAppsHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(ListInstalledAppsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof ListInstalledAppsUseCaseInterface) {
                        throw new LogicException('ListInstalledApps use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListInstalledAppsHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                'nene-suite.route_registrar.installed_apps',
                static function (ContainerInterface $container): InstalledAppsRouteRegistrar {
                    $listHandler = $container->get(ListInstalledAppsHandler::class);

                    if (!$listHandler instanceof ListInstalledAppsHandler) {
                        throw new LogicException('ListInstalledApps handler service is invalid.');
                    }

                    return new InstalledAppsRouteRegistrar($listHandler);
                },
            );
    }
}
