<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use LogicException;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\Origin\GetOriginUpdatesUseCaseInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use NeNeSuite\Tenancy\SuperadminGuard;
use Psr\Container\ContainerInterface;

/**
 * Wires the deploy-control seam (ADR 0019 OQ1, S2-1a): capability config, the deploy
 * request queue, the agent authenticator, and the operator/machine handlers.
 */
final readonly class DeployServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DeployAgentConfig::class,
                static fn (ContainerInterface $container): DeployAgentConfig => (new DeployAgentConfigResolver())->resolve(),
            )
            ->set(
                DeployAgentAuthenticator::class,
                static fn (ContainerInterface $container): DeployAgentAuthenticator => new DeployAgentAuthenticator(
                    self::config($container),
                ),
            )
            ->set(
                DeployRequestRepositoryFactoryInterface::class,
                static fn (ContainerInterface $container): DeployRequestRepositoryFactoryInterface => new PdoDeployRequestRepositoryFactory(),
            )
            ->set(
                CreateDeployRequestUseCaseInterface::class,
                static fn (ContainerInterface $container): CreateDeployRequestUseCaseInterface => new CreateDeployRequestUseCase(
                    self::config($container),
                    self::catalog($container),
                    self::transactionManager($container),
                    self::repositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                ListDeployRequestsUseCaseInterface::class,
                static fn (ContainerInterface $container): ListDeployRequestsUseCaseInterface => new ListDeployRequestsUseCase(
                    self::config($container),
                    self::transactionManager($container),
                    self::repositoryFactory($container),
                ),
            )
            ->set(
                ListPendingDeployRequestsUseCaseInterface::class,
                static fn (ContainerInterface $container): ListPendingDeployRequestsUseCaseInterface => new ListPendingDeployRequestsUseCase(
                    self::transactionManager($container),
                    self::repositoryFactory($container),
                ),
            )
            ->set(
                ReportDeployResultUseCaseInterface::class,
                static fn (ContainerInterface $container): ReportDeployResultUseCaseInterface => new ReportDeployResultUseCase(
                    self::transactionManager($container),
                    self::repositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                ComputeDeployPlanUseCaseInterface::class,
                static fn (ContainerInterface $container): ComputeDeployPlanUseCaseInterface => new ComputeDeployPlanUseCase(
                    self::config($container),
                    self::originUpdates($container),
                    self::catalog($container),
                ),
            )
            ->set(
                GetDeployPlanHandler::class,
                static fn (ContainerInterface $container): GetDeployPlanHandler => new GetDeployPlanHandler(
                    self::superadminGuard($container),
                    self::planUseCase($container),
                    self::jsonResponse($container),
                ),
            )
            ->set(
                CreateDeployRequestHandler::class,
                static fn (ContainerInterface $container): CreateDeployRequestHandler => new CreateDeployRequestHandler(
                    self::superadminGuard($container),
                    self::createUseCase($container),
                    self::jsonResponse($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                ListDeployRequestsHandler::class,
                static fn (ContainerInterface $container): ListDeployRequestsHandler => new ListDeployRequestsHandler(
                    self::superadminGuard($container),
                    self::listUseCase($container),
                    self::jsonResponse($container),
                ),
            )
            ->set(
                ListPendingDeployRequestsHandler::class,
                static fn (ContainerInterface $container): ListPendingDeployRequestsHandler => new ListPendingDeployRequestsHandler(
                    self::authenticator($container),
                    self::listPendingUseCase($container),
                    self::jsonResponse($container),
                ),
            )
            ->set(
                ReportDeployRequestResultHandler::class,
                static fn (ContainerInterface $container): ReportDeployRequestResultHandler => new ReportDeployRequestResultHandler(
                    self::authenticator($container),
                    self::reportUseCase($container),
                    self::jsonResponse($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                DeployCapabilityDisabledExceptionHandler::class,
                static fn (ContainerInterface $container): DeployCapabilityDisabledExceptionHandler => new DeployCapabilityDisabledExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                DeployAgentUnauthorizedExceptionHandler::class,
                static fn (ContainerInterface $container): DeployAgentUnauthorizedExceptionHandler => new DeployAgentUnauthorizedExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                DeployRequestNotFoundExceptionHandler::class,
                static fn (ContainerInterface $container): DeployRequestNotFoundExceptionHandler => new DeployRequestNotFoundExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                DeployRequestConflictExceptionHandler::class,
                static fn (ContainerInterface $container): DeployRequestConflictExceptionHandler => new DeployRequestConflictExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                DeployValidationExceptionHandler::class,
                static fn (ContainerInterface $container): DeployValidationExceptionHandler => new DeployValidationExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                'nene-suite.route_registrar.deploy',
                static fn (ContainerInterface $container): DeployRouteRegistrar => new DeployRouteRegistrar(
                    self::get($container, CreateDeployRequestHandler::class),
                    self::get($container, ListDeployRequestsHandler::class),
                    self::get($container, GetDeployPlanHandler::class),
                    self::get($container, ListPendingDeployRequestsHandler::class),
                    self::get($container, ReportDeployRequestResultHandler::class),
                ),
            );
    }

    private static function config(ContainerInterface $container): DeployAgentConfig
    {
        $config = $container->get(DeployAgentConfig::class);

        if (!$config instanceof DeployAgentConfig) {
            throw new LogicException('Deploy agent config service is invalid.');
        }

        return $config;
    }

    private static function authenticator(ContainerInterface $container): DeployAgentAuthenticator
    {
        $authenticator = $container->get(DeployAgentAuthenticator::class);

        if (!$authenticator instanceof DeployAgentAuthenticator) {
            throw new LogicException('Deploy agent authenticator service is invalid.');
        }

        return $authenticator;
    }

    private static function originUpdates(ContainerInterface $container): GetOriginUpdatesUseCaseInterface
    {
        $useCase = $container->get(GetOriginUpdatesUseCaseInterface::class);

        if (!$useCase instanceof GetOriginUpdatesUseCaseInterface) {
            throw new LogicException('Origin updates use case service is invalid.');
        }

        return $useCase;
    }

    private static function planUseCase(ContainerInterface $container): ComputeDeployPlanUseCaseInterface
    {
        $useCase = $container->get(ComputeDeployPlanUseCaseInterface::class);

        if (!$useCase instanceof ComputeDeployPlanUseCaseInterface) {
            throw new LogicException('Compute deploy plan use case service is invalid.');
        }

        return $useCase;
    }

    private static function catalog(ContainerInterface $container): CatalogAppRepositoryInterface
    {
        $catalog = $container->get(CatalogAppRepositoryInterface::class);

        if (!$catalog instanceof CatalogAppRepositoryInterface) {
            throw new LogicException('Catalog app repository service is invalid.');
        }

        return $catalog;
    }

    private static function transactionManager(ContainerInterface $container): DatabaseTransactionManagerInterface
    {
        $transactions = $container->get(DatabaseTransactionManagerInterface::class);

        if (!$transactions instanceof DatabaseTransactionManagerInterface) {
            throw new LogicException('Database transaction manager service is invalid.');
        }

        return $transactions;
    }

    private static function repositoryFactory(ContainerInterface $container): DeployRequestRepositoryFactoryInterface
    {
        $factory = $container->get(DeployRequestRepositoryFactoryInterface::class);

        if (!$factory instanceof DeployRequestRepositoryFactoryInterface) {
            throw new LogicException('Deploy request repository factory service is invalid.');
        }

        return $factory;
    }

    private static function auditRecorderFactory(ContainerInterface $container): SuiteAuditRecorderFactoryInterface
    {
        $factory = $container->get(SuiteAuditRecorderFactoryInterface::class);

        if (!$factory instanceof SuiteAuditRecorderFactoryInterface) {
            throw new LogicException('Suite audit recorder factory service is invalid.');
        }

        return $factory;
    }

    private static function suiteId(ContainerInterface $container): string
    {
        $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

        if (!is_string($suiteId) || $suiteId === '') {
            throw new LogicException('Suite id service is invalid.');
        }

        return $suiteId;
    }

    private static function superadminGuard(ContainerInterface $container): SuperadminGuard
    {
        $guard = $container->get(SuperadminGuard::class);

        if (!$guard instanceof SuperadminGuard) {
            throw new LogicException('Superadmin guard service is invalid.');
        }

        return $guard;
    }

    private static function createUseCase(ContainerInterface $container): CreateDeployRequestUseCaseInterface
    {
        $useCase = $container->get(CreateDeployRequestUseCaseInterface::class);

        if (!$useCase instanceof CreateDeployRequestUseCaseInterface) {
            throw new LogicException('Create deploy request use case service is invalid.');
        }

        return $useCase;
    }

    private static function listUseCase(ContainerInterface $container): ListDeployRequestsUseCaseInterface
    {
        $useCase = $container->get(ListDeployRequestsUseCaseInterface::class);

        if (!$useCase instanceof ListDeployRequestsUseCaseInterface) {
            throw new LogicException('List deploy requests use case service is invalid.');
        }

        return $useCase;
    }

    private static function listPendingUseCase(ContainerInterface $container): ListPendingDeployRequestsUseCaseInterface
    {
        $useCase = $container->get(ListPendingDeployRequestsUseCaseInterface::class);

        if (!$useCase instanceof ListPendingDeployRequestsUseCaseInterface) {
            throw new LogicException('List pending deploy requests use case service is invalid.');
        }

        return $useCase;
    }

    private static function reportUseCase(ContainerInterface $container): ReportDeployResultUseCaseInterface
    {
        $useCase = $container->get(ReportDeployResultUseCaseInterface::class);

        if (!$useCase instanceof ReportDeployResultUseCaseInterface) {
            throw new LogicException('Report deploy result use case service is invalid.');
        }

        return $useCase;
    }

    private static function jsonResponse(ContainerInterface $container): JsonResponseFactory
    {
        $response = $container->get(JsonResponseFactory::class);

        if (!$response instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $response;
    }

    private static function requestIdHolder(ContainerInterface $container): RequestIdHolder
    {
        $holder = $container->get(RequestIdHolder::class);

        if (!$holder instanceof RequestIdHolder) {
            throw new LogicException('Request id holder service is invalid.');
        }

        return $holder;
    }

    private static function problemDetails(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

        if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problemDetails;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    private static function get(ContainerInterface $container, string $id): object
    {
        $service = $container->get($id);

        if (!$service instanceof $id) {
            throw new LogicException(sprintf('%s service is invalid.', $id));
        }

        return $service;
    }
}
