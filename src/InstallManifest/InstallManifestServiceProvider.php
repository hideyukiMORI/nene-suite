<?php

declare(strict_types=1);

namespace NeNeSuite\InstallManifest;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

final readonly class InstallManifestServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(InstallManifestFactory::class, static fn (ContainerInterface $container): InstallManifestFactory => new InstallManifestFactory())
            ->set(
                InstallManifestRepositoryInterface::class,
                static function (ContainerInterface $container): InstallManifestRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoInstallManifestRepository($query);
                },
            );
    }
}
