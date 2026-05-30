<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

final readonly class SuiteEnvServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->set(
            SuiteAppUrlReaderInterface::class,
            static fn (ContainerInterface $container): SuiteAppUrlReaderInterface => new EnvSuiteAppUrlReader(),
        );
    }
}
