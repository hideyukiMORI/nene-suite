<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Psr\Container\ContainerInterface;

final readonly class InstallSessionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                InstallSessionRepositoryInterface::class,
                static function (ContainerInterface $container): InstallSessionRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoInstallSessionRepository($query);
                },
            )
            ->set(
                StartInstallSessionUseCaseInterface::class,
                static function (ContainerInterface $container): StartInstallSessionUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);
                    $catalog = $container->get(CatalogAppRepositoryInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    if (!$catalog instanceof CatalogAppRepositoryInterface) {
                        throw new LogicException('Catalog app repository service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new StartInstallSessionUseCase($sessions, $audit, $catalog, $suiteId);
                },
            )
            ->set(
                StartInstallSessionHandler::class,
                static function (ContainerInterface $container): StartInstallSessionHandler {
                    $useCase = $container->get(StartInstallSessionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);

                    if (!$useCase instanceof StartInstallSessionUseCaseInterface) {
                        throw new LogicException('StartInstallSession use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    return new StartInstallSessionHandler($useCase, $response, $requestIdHolder);
                },
            )
            ->set(
                GetInstallSessionUseCaseInterface::class,
                static function (ContainerInterface $container): GetInstallSessionUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    return new GetInstallSessionUseCase($sessions);
                },
            )
            ->set(
                GetInstallSessionHandler::class,
                static function (ContainerInterface $container): GetInstallSessionHandler {
                    $useCase = $container->get(GetInstallSessionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetInstallSessionUseCaseInterface) {
                        throw new LogicException('GetInstallSession use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetInstallSessionHandler($useCase, $response);
                },
            )
            ->set(
                InstallSessionNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): InstallSessionNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new InstallSessionNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                InstallSessionConflictExceptionHandler::class,
                static function (ContainerInterface $container): InstallSessionConflictExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new InstallSessionConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-suite.route_registrar.install_session',
                static function (ContainerInterface $container): InstallSessionRouteRegistrar {
                    $start = $container->get(StartInstallSessionHandler::class);
                    $get = $container->get(GetInstallSessionHandler::class);

                    if (!$start instanceof StartInstallSessionHandler) {
                        throw new LogicException('StartInstallSession handler service is invalid.');
                    }

                    if (!$get instanceof GetInstallSessionHandler) {
                        throw new LogicException('GetInstallSession handler service is invalid.');
                    }

                    return new InstallSessionRouteRegistrar($start, $get);
                },
            );
    }
}
