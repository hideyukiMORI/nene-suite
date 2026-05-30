<?php

declare(strict_types=1);

namespace NeNeSuite;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\AppCatalog\AppCatalogRouteRegistrar;
use NeNeSuite\AppCatalog\AppCatalogServiceProvider;
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
        $builder->addProvider(new AppCatalogServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $appCatalog = $container->get('nene-suite.route_registrar.app_catalog');

                    if (!$appCatalog instanceof AppCatalogRouteRegistrar) {
                        throw new LogicException('App catalog route registrar service is invalid.');
                    }

                    return [
                        $appCatalog,
                    ];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static fn (ContainerInterface $container): array => [],
            );
    }
}
