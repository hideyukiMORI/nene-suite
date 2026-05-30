<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use NeNeSuite\AppCatalog\AppDependencyResolver;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Psr\Container\ContainerInterface;

final readonly class AppSelectionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UpdateAppSelectionUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateAppSelectionUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $catalog = $container->get(CatalogAppRepositoryInterface::class);
                    $resolver = $container->get(AppDependencyResolver::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$catalog instanceof CatalogAppRepositoryInterface) {
                        throw new LogicException('Catalog app repository service is invalid.');
                    }

                    if (!$resolver instanceof AppDependencyResolver) {
                        throw new LogicException('App dependency resolver service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    return new UpdateAppSelectionUseCase($sessions, $catalog, $resolver, $audit);
                },
            )
            ->set(
                UpdateAppSelectionHandler::class,
                static function (ContainerInterface $container): UpdateAppSelectionHandler {
                    $useCase = $container->get(UpdateAppSelectionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);

                    if (!$useCase instanceof UpdateAppSelectionUseCaseInterface) {
                        throw new LogicException('UpdateAppSelection use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    return new UpdateAppSelectionHandler($useCase, $response, $requestIdHolder);
                },
            )
            ->set(
                'nene-suite.route_registrar.app_selection',
                static function (ContainerInterface $container): AppSelectionRouteRegistrar {
                    $update = $container->get(UpdateAppSelectionHandler::class);

                    if (!$update instanceof UpdateAppSelectionHandler) {
                        throw new LogicException('UpdateAppSelection handler service is invalid.');
                    }

                    return new AppSelectionRouteRegistrar($update);
                },
            );
    }
}
