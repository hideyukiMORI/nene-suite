<?php

declare(strict_types=1);

namespace NeNeSuite;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeNeSuite\AppCatalog\AppCatalogRouteRegistrar;
use NeNeSuite\AppCatalog\AppCatalogServiceProvider;
use NeNeSuite\AppSelection\AppSelectionRouteRegistrar;
use NeNeSuite\AppSelection\AppSelectionServiceProvider;
use NeNeSuite\InstallManifest\InstallManifestServiceProvider;
use NeNeSuite\InstallSession\InstallSessionConflictExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionNotFoundExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionNotReadyExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionRouteRegistrar;
use NeNeSuite\InstallSession\InstallSessionServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Aggregates suite orchestration domain providers and exposes the route
 * registrar / exception handler lists consumed by the runtime factory.
 * No business logic lives here.
 */
final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-suite.route_registrars';

    public const EXCEPTION_HANDLERS = 'nene-suite.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->addProvider(new AppCatalogServiceProvider())
            ->addProvider(new SuiteAuditServiceProvider())
            ->addProvider(new InstallManifestServiceProvider())
            ->addProvider(new InstallSessionServiceProvider())
            ->addProvider(new AppSelectionServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $appCatalog = $container->get('nene-suite.route_registrar.app_catalog');
                    $installSession = $container->get('nene-suite.route_registrar.install_session');
                    $appSelection = $container->get('nene-suite.route_registrar.app_selection');

                    if (!$appCatalog instanceof AppCatalogRouteRegistrar) {
                        throw new LogicException('App catalog route registrar service is invalid.');
                    }

                    if (!$installSession instanceof InstallSessionRouteRegistrar) {
                        throw new LogicException('Install session route registrar service is invalid.');
                    }

                    if (!$appSelection instanceof AppSelectionRouteRegistrar) {
                        throw new LogicException('App selection route registrar service is invalid.');
                    }

                    return [
                        $appCatalog,
                        $installSession,
                        $appSelection,
                    ];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $installSessionNotFound = $container->get(InstallSessionNotFoundExceptionHandler::class);
                    $installSessionConflict = $container->get(InstallSessionConflictExceptionHandler::class);
                    $installSessionNotReady = $container->get(InstallSessionNotReadyExceptionHandler::class);

                    if (!$installSessionNotFound instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session not found exception handler service is invalid.');
                    }

                    if (!$installSessionConflict instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session conflict exception handler service is invalid.');
                    }

                    if (!$installSessionNotReady instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session not ready exception handler service is invalid.');
                    }

                    return [
                        $installSessionNotFound,
                        $installSessionConflict,
                        $installSessionNotReady,
                    ];
                },
            );
    }
}
