<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Psr\Container\ContainerInterface;

final readonly class TenancyServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationRepositoryInterface::class,
                static function (ContainerInterface $container): OrganizationRepositoryInterface {
                    return new PdoOrganizationRepository(self::queryExecutor($container));
                },
            )
            ->set(
                MembershipRepositoryInterface::class,
                static function (ContainerInterface $container): MembershipRepositoryInterface {
                    return new PdoMembershipRepository(self::queryExecutor($container));
                },
            )
            ->set(
                OrganizationRepositoryFactoryInterface::class,
                static fn (ContainerInterface $container): OrganizationRepositoryFactoryInterface => new PdoOrganizationRepositoryFactory(),
            )
            ->set(
                CreateOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $container): CreateOrganizationUseCaseInterface => new CreateOrganizationUseCase(
                    self::transactionManager($container),
                    self::organizationRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                RenameOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $container): RenameOrganizationUseCaseInterface => new RenameOrganizationUseCase(
                    self::transactionManager($container),
                    self::organizationRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                DisableOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $container): DisableOrganizationUseCaseInterface => new DisableOrganizationUseCase(
                    self::transactionManager($container),
                    self::organizationRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                MembershipRepositoryFactoryInterface::class,
                static fn (ContainerInterface $container): MembershipRepositoryFactoryInterface => new PdoMembershipRepositoryFactory(),
            )
            ->set(
                GrantMembershipUseCaseInterface::class,
                static fn (ContainerInterface $container): GrantMembershipUseCaseInterface => new GrantMembershipUseCase(
                    self::transactionManager($container),
                    self::membershipRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                ChangeMembershipRoleUseCaseInterface::class,
                static fn (ContainerInterface $container): ChangeMembershipRoleUseCaseInterface => new ChangeMembershipRoleUseCase(
                    self::transactionManager($container),
                    self::membershipRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                RevokeMembershipUseCaseInterface::class,
                static fn (ContainerInterface $container): RevokeMembershipUseCaseInterface => new RevokeMembershipUseCase(
                    self::transactionManager($container),
                    self::membershipRepositoryFactory($container),
                    self::auditRecorderFactory($container),
                    self::suiteId($container),
                ),
            )
            ->set(
                ListOrganizationsUseCaseInterface::class,
                static fn (ContainerInterface $container): ListOrganizationsUseCaseInterface => new ListOrganizationsUseCase(
                    self::organizationRepository($container),
                ),
            )
            ->set(
                SuperadminGuard::class,
                static fn (ContainerInterface $container): SuperadminGuard => new SuperadminGuard(
                    self::bearerAuthenticator($container),
                ),
            )
            ->set(
                CreateOrganizationHandler::class,
                static fn (ContainerInterface $container): CreateOrganizationHandler => new CreateOrganizationHandler(
                    self::superadminGuard($container),
                    self::createOrganizationUseCase($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                ListOrganizationsHandler::class,
                static fn (ContainerInterface $container): ListOrganizationsHandler => new ListOrganizationsHandler(
                    self::superadminGuard($container),
                    self::listOrganizationsUseCase($container),
                    self::responseFactory($container),
                ),
            )
            ->set(
                RenameOrganizationHandler::class,
                static fn (ContainerInterface $container): RenameOrganizationHandler => new RenameOrganizationHandler(
                    self::superadminGuard($container),
                    self::renameOrganizationUseCase($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                DisableOrganizationHandler::class,
                static fn (ContainerInterface $container): DisableOrganizationHandler => new DisableOrganizationHandler(
                    self::superadminGuard($container),
                    self::disableOrganizationUseCase($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                'nene-suite.route_registrar.tenancy',
                static fn (ContainerInterface $container): OrganizationRouteRegistrar => new OrganizationRouteRegistrar(
                    self::createOrganizationHandler($container),
                    self::listOrganizationsHandler($container),
                    self::renameOrganizationHandler($container),
                    self::disableOrganizationHandler($container),
                ),
            )
            ->set(
                ForbiddenExceptionHandler::class,
                static fn (ContainerInterface $container): ForbiddenExceptionHandler => new ForbiddenExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                OrganizationValidationExceptionHandler::class,
                static fn (ContainerInterface $container): OrganizationValidationExceptionHandler => new OrganizationValidationExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                OrganizationSlugConflictExceptionHandler::class,
                static fn (ContainerInterface $container): OrganizationSlugConflictExceptionHandler => new OrganizationSlugConflictExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                OrganizationNotFoundExceptionHandler::class,
                static fn (ContainerInterface $container): OrganizationNotFoundExceptionHandler => new OrganizationNotFoundExceptionHandler(
                    self::problemDetails($container),
                ),
            );
    }

    private static function queryExecutor(ContainerInterface $container): DatabaseQueryExecutorInterface
    {
        $query = $container->get(DatabaseQueryExecutorInterface::class);

        if (!$query instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $query;
    }

    private static function transactionManager(ContainerInterface $container): DatabaseTransactionManagerInterface
    {
        $transactions = $container->get(DatabaseTransactionManagerInterface::class);

        if (!$transactions instanceof DatabaseTransactionManagerInterface) {
            throw new LogicException('Database transaction manager service is invalid.');
        }

        return $transactions;
    }

    private static function organizationRepositoryFactory(ContainerInterface $container): OrganizationRepositoryFactoryInterface
    {
        $factory = $container->get(OrganizationRepositoryFactoryInterface::class);

        if (!$factory instanceof OrganizationRepositoryFactoryInterface) {
            throw new LogicException('Organization repository factory service is invalid.');
        }

        return $factory;
    }

    private static function membershipRepositoryFactory(ContainerInterface $container): MembershipRepositoryFactoryInterface
    {
        $factory = $container->get(MembershipRepositoryFactoryInterface::class);

        if (!$factory instanceof MembershipRepositoryFactoryInterface) {
            throw new LogicException('Membership repository factory service is invalid.');
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

    private static function organizationRepository(ContainerInterface $container): OrganizationRepositoryInterface
    {
        $repository = $container->get(OrganizationRepositoryInterface::class);

        if (!$repository instanceof OrganizationRepositoryInterface) {
            throw new LogicException('Organization repository service is invalid.');
        }

        return $repository;
    }

    private static function bearerAuthenticator(ContainerInterface $container): BearerTokenAuthenticator
    {
        $authenticator = $container->get(BearerTokenAuthenticator::class);

        if (!$authenticator instanceof BearerTokenAuthenticator) {
            throw new LogicException('Bearer token authenticator service is invalid.');
        }

        return $authenticator;
    }

    private static function superadminGuard(ContainerInterface $container): SuperadminGuard
    {
        $guard = $container->get(SuperadminGuard::class);

        if (!$guard instanceof SuperadminGuard) {
            throw new LogicException('Superadmin guard service is invalid.');
        }

        return $guard;
    }

    private static function responseFactory(ContainerInterface $container): JsonResponseFactory
    {
        $factory = $container->get(JsonResponseFactory::class);

        if (!$factory instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $factory;
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
        $factory = $container->get(ProblemDetailsResponseFactory::class);

        if (!$factory instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $factory;
    }

    private static function createOrganizationUseCase(ContainerInterface $container): CreateOrganizationUseCaseInterface
    {
        $useCase = $container->get(CreateOrganizationUseCaseInterface::class);

        if (!$useCase instanceof CreateOrganizationUseCaseInterface) {
            throw new LogicException('Create organization use case service is invalid.');
        }

        return $useCase;
    }

    private static function listOrganizationsUseCase(ContainerInterface $container): ListOrganizationsUseCaseInterface
    {
        $useCase = $container->get(ListOrganizationsUseCaseInterface::class);

        if (!$useCase instanceof ListOrganizationsUseCaseInterface) {
            throw new LogicException('List organizations use case service is invalid.');
        }

        return $useCase;
    }

    private static function renameOrganizationUseCase(ContainerInterface $container): RenameOrganizationUseCaseInterface
    {
        $useCase = $container->get(RenameOrganizationUseCaseInterface::class);

        if (!$useCase instanceof RenameOrganizationUseCaseInterface) {
            throw new LogicException('Rename organization use case service is invalid.');
        }

        return $useCase;
    }

    private static function disableOrganizationUseCase(ContainerInterface $container): DisableOrganizationUseCaseInterface
    {
        $useCase = $container->get(DisableOrganizationUseCaseInterface::class);

        if (!$useCase instanceof DisableOrganizationUseCaseInterface) {
            throw new LogicException('Disable organization use case service is invalid.');
        }

        return $useCase;
    }

    private static function createOrganizationHandler(ContainerInterface $container): CreateOrganizationHandler
    {
        $handler = $container->get(CreateOrganizationHandler::class);

        if (!$handler instanceof CreateOrganizationHandler) {
            throw new LogicException('Create organization handler service is invalid.');
        }

        return $handler;
    }

    private static function listOrganizationsHandler(ContainerInterface $container): ListOrganizationsHandler
    {
        $handler = $container->get(ListOrganizationsHandler::class);

        if (!$handler instanceof ListOrganizationsHandler) {
            throw new LogicException('List organizations handler service is invalid.');
        }

        return $handler;
    }

    private static function renameOrganizationHandler(ContainerInterface $container): RenameOrganizationHandler
    {
        $handler = $container->get(RenameOrganizationHandler::class);

        if (!$handler instanceof RenameOrganizationHandler) {
            throw new LogicException('Rename organization handler service is invalid.');
        }

        return $handler;
    }

    private static function disableOrganizationHandler(ContainerInterface $container): DisableOrganizationHandler
    {
        $handler = $container->get(DisableOrganizationHandler::class);

        if (!$handler instanceof DisableOrganizationHandler) {
            throw new LogicException('Disable organization handler service is invalid.');
        }

        return $handler;
    }
}
