<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\SuiteEnv\SuiteAppMachineKeyReaderInterface;
use Psr\Container\ContainerInterface;

/**
 * Sibling-health services: the outbound auth-gated `/machine/health` probe (installed-version
 * source), the control-DB version cache, and the resolver that combines them — with the per-app
 * machine key from the suite env — for the Origin update aggregator (#255 / #257). No routes or
 * domain exceptions: this slice is consumed by the Origin read path, not served.
 */
final readonly class SiblingHealthServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SiblingHealthClientInterface::class,
                static fn (ContainerInterface $container): SiblingHealthClientInterface => new StreamSiblingHealthClient(),
            )
            ->set(
                InstalledVersionRepositoryInterface::class,
                static function (ContainerInterface $container): InstalledVersionRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoInstalledVersionRepository($query);
                },
            )
            ->set(
                InstalledVersionResolverInterface::class,
                static function (ContainerInterface $container): InstalledVersionResolverInterface {
                    $client = $container->get(SiblingHealthClientInterface::class);
                    $repository = $container->get(InstalledVersionRepositoryInterface::class);
                    $machineKeys = $container->get(SuiteAppMachineKeyReaderInterface::class);

                    if (!$client instanceof SiblingHealthClientInterface) {
                        throw new LogicException('Sibling health client service is invalid.');
                    }

                    if (!$repository instanceof InstalledVersionRepositoryInterface) {
                        throw new LogicException('Installed version repository service is invalid.');
                    }

                    if (!$machineKeys instanceof SuiteAppMachineKeyReaderInterface) {
                        throw new LogicException('Suite app machine key reader service is invalid.');
                    }

                    return new InstalledVersionResolver($client, $repository, $machineKeys);
                },
            );
    }
}
