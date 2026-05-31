<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\AppSelection\UpdateAppSelectionUseCaseInterface;
use NeNeSuite\Auth\CreateOperatorUseCaseInterface;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesUseCaseInterface;
use NeNeSuite\InstallSession\AcceptDisclaimerUseCaseInterface;
use NeNeSuite\InstallSession\CompleteInstallSessionUseCaseInterface;
use NeNeSuite\InstallSession\StartInstallSessionUseCaseInterface;
use NeNeSuite\SuiteEnv\WriteEnvConfigUseCaseInterface;
use Psr\Container\ContainerInterface;

final readonly class InstallerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                InstallerEnvReader::class,
                static fn (ContainerInterface $container): InstallerEnvReader => new InstallerEnvReader(),
            )
            ->set(
                InstallerUseCaseInterface::class,
                static function (ContainerInterface $container): InstallerUseCaseInterface {
                    $startSession = $container->get(StartInstallSessionUseCaseInterface::class);
                    $updateSelection = $container->get(UpdateAppSelectionUseCaseInterface::class);
                    $acceptDisclaimer = $container->get(AcceptDisclaimerUseCaseInterface::class);
                    $provisionDatabases = $container->get(ProvisionAppDatabasesUseCaseInterface::class);
                    $writeEnvConfig = $container->get(WriteEnvConfigUseCaseInterface::class);
                    $createOperator = $container->get(CreateOperatorUseCaseInterface::class);
                    $completeSession = $container->get(CompleteInstallSessionUseCaseInterface::class);

                    if (!$startSession instanceof StartInstallSessionUseCaseInterface) {
                        throw new LogicException('StartInstallSession use case service is invalid.');
                    }

                    if (!$updateSelection instanceof UpdateAppSelectionUseCaseInterface) {
                        throw new LogicException('UpdateAppSelection use case service is invalid.');
                    }

                    if (!$acceptDisclaimer instanceof AcceptDisclaimerUseCaseInterface) {
                        throw new LogicException('AcceptDisclaimer use case service is invalid.');
                    }

                    if (!$provisionDatabases instanceof ProvisionAppDatabasesUseCaseInterface) {
                        throw new LogicException('ProvisionAppDatabases use case service is invalid.');
                    }

                    if (!$writeEnvConfig instanceof WriteEnvConfigUseCaseInterface) {
                        throw new LogicException('WriteEnvConfig use case service is invalid.');
                    }

                    if (!$createOperator instanceof CreateOperatorUseCaseInterface) {
                        throw new LogicException('CreateOperator use case service is invalid.');
                    }

                    if (!$completeSession instanceof CompleteInstallSessionUseCaseInterface) {
                        throw new LogicException('CompleteInstallSession use case service is invalid.');
                    }

                    return new InstallerUseCase(
                        $startSession,
                        $updateSelection,
                        $acceptDisclaimer,
                        $provisionDatabases,
                        $writeEnvConfig,
                        $createOperator,
                        $completeSession,
                    );
                },
            );
    }
}
