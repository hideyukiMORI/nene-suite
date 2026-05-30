<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

final readonly class DatabaseProvisionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->set(
            AppDatabaseNamer::class,
            static fn (ContainerInterface $container): AppDatabaseNamer => new AppDatabaseNamer(),
        );
    }
}
