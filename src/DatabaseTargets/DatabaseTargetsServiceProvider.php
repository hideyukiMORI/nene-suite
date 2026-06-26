<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Psr\Container\ContainerInterface;

final readonly class DatabaseTargetsServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SetDatabaseTargetsUseCaseInterface::class,
                static function (ContainerInterface $container): SetDatabaseTargetsUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $factory = $container->get(DatabaseTargetFactory::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$factory instanceof DatabaseTargetFactory) {
                        throw new LogicException('Database target factory service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    return new SetDatabaseTargetsUseCase($sessions, $factory, $audit);
                },
            )
            ->set(
                SetDatabaseTargetsHandler::class,
                static function (ContainerInterface $container): SetDatabaseTargetsHandler {
                    $useCase = $container->get(SetDatabaseTargetsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);

                    if (!$useCase instanceof SetDatabaseTargetsUseCaseInterface) {
                        throw new LogicException('SetDatabaseTargets use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    return new SetDatabaseTargetsHandler($useCase, $response, $requestIdHolder);
                },
            )
            ->set(
                'nene-suite.route_registrar.database_targets',
                static function (ContainerInterface $container): DatabaseTargetsRouteRegistrar {
                    $set = $container->get(SetDatabaseTargetsHandler::class);

                    if (!$set instanceof SetDatabaseTargetsHandler) {
                        throw new LogicException('SetDatabaseTargets handler service is invalid.');
                    }

                    return new DatabaseTargetsRouteRegistrar($set);
                },
            );
    }
}
