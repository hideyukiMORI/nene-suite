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
use NeNeSuite\Auth\OperatorRepositoryInterface;
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
                EnableOrganizationUseCaseInterface::class,
                static fn (ContainerInterface $container): EnableOrganizationUseCaseInterface => new EnableOrganizationUseCase(
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
                EnableOrganizationHandler::class,
                static fn (ContainerInterface $container): EnableOrganizationHandler => new EnableOrganizationHandler(
                    self::superadminGuard($container),
                    self::enableOrganizationUseCase($container),
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
                    self::enableOrganizationHandler($container),
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
            )
            ->set(
                ListOrganizationMembershipsUseCaseInterface::class,
                static fn (ContainerInterface $container): ListOrganizationMembershipsUseCaseInterface => new ListOrganizationMembershipsUseCase(
                    self::membershipRepository($container),
                    self::operatorRepository($container),
                ),
            )
            ->set(
                ListOrganizationMembershipsHandler::class,
                static fn (ContainerInterface $container): ListOrganizationMembershipsHandler => new ListOrganizationMembershipsHandler(
                    self::superadminGuard($container),
                    self::listOrganizationMembershipsUseCase($container),
                    self::organizationRepository($container),
                    self::responseFactory($container),
                ),
            )
            ->set(
                GrantMembershipHandler::class,
                static fn (ContainerInterface $container): GrantMembershipHandler => new GrantMembershipHandler(
                    self::superadminGuard($container),
                    self::grantMembershipUseCase($container),
                    self::organizationRepository($container),
                    self::operatorRepository($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                ChangeMembershipRoleHandler::class,
                static fn (ContainerInterface $container): ChangeMembershipRoleHandler => new ChangeMembershipRoleHandler(
                    self::superadminGuard($container),
                    self::changeMembershipRoleUseCase($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                RevokeMembershipHandler::class,
                static fn (ContainerInterface $container): RevokeMembershipHandler => new RevokeMembershipHandler(
                    self::superadminGuard($container),
                    self::revokeMembershipUseCase($container),
                    self::responseFactory($container),
                    self::requestIdHolder($container),
                ),
            )
            ->set(
                'nene-suite.route_registrar.memberships',
                static fn (ContainerInterface $container): MembershipRouteRegistrar => new MembershipRouteRegistrar(
                    self::listOrganizationMembershipsHandler($container),
                    self::grantMembershipHandler($container),
                    self::changeMembershipRoleHandler($container),
                    self::revokeMembershipHandler($container),
                ),
            )
            ->set(
                MembershipValidationExceptionHandler::class,
                static fn (ContainerInterface $container): MembershipValidationExceptionHandler => new MembershipValidationExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                MembershipConflictExceptionHandler::class,
                static fn (ContainerInterface $container): MembershipConflictExceptionHandler => new MembershipConflictExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                MembershipInvariantExceptionHandler::class,
                static fn (ContainerInterface $container): MembershipInvariantExceptionHandler => new MembershipInvariantExceptionHandler(
                    self::problemDetails($container),
                ),
            )
            ->set(
                MembershipNotFoundExceptionHandler::class,
                static fn (ContainerInterface $container): MembershipNotFoundExceptionHandler => new MembershipNotFoundExceptionHandler(
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

    private static function enableOrganizationUseCase(ContainerInterface $container): EnableOrganizationUseCaseInterface
    {
        $useCase = $container->get(EnableOrganizationUseCaseInterface::class);

        if (!$useCase instanceof EnableOrganizationUseCaseInterface) {
            throw new LogicException('Enable organization use case service is invalid.');
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

    private static function enableOrganizationHandler(ContainerInterface $container): EnableOrganizationHandler
    {
        $handler = $container->get(EnableOrganizationHandler::class);

        if (!$handler instanceof EnableOrganizationHandler) {
            throw new LogicException('Enable organization handler service is invalid.');
        }

        return $handler;
    }

    private static function grantMembershipUseCase(ContainerInterface $container): GrantMembershipUseCaseInterface
    {
        $useCase = $container->get(GrantMembershipUseCaseInterface::class);

        if (!$useCase instanceof GrantMembershipUseCaseInterface) {
            throw new LogicException('Grant membership use case service is invalid.');
        }

        return $useCase;
    }

    private static function changeMembershipRoleUseCase(ContainerInterface $container): ChangeMembershipRoleUseCaseInterface
    {
        $useCase = $container->get(ChangeMembershipRoleUseCaseInterface::class);

        if (!$useCase instanceof ChangeMembershipRoleUseCaseInterface) {
            throw new LogicException('Change membership role use case service is invalid.');
        }

        return $useCase;
    }

    private static function revokeMembershipUseCase(ContainerInterface $container): RevokeMembershipUseCaseInterface
    {
        $useCase = $container->get(RevokeMembershipUseCaseInterface::class);

        if (!$useCase instanceof RevokeMembershipUseCaseInterface) {
            throw new LogicException('Revoke membership use case service is invalid.');
        }

        return $useCase;
    }

    private static function grantMembershipHandler(ContainerInterface $container): GrantMembershipHandler
    {
        $handler = $container->get(GrantMembershipHandler::class);

        if (!$handler instanceof GrantMembershipHandler) {
            throw new LogicException('Grant membership handler service is invalid.');
        }

        return $handler;
    }

    private static function changeMembershipRoleHandler(ContainerInterface $container): ChangeMembershipRoleHandler
    {
        $handler = $container->get(ChangeMembershipRoleHandler::class);

        if (!$handler instanceof ChangeMembershipRoleHandler) {
            throw new LogicException('Change membership role handler service is invalid.');
        }

        return $handler;
    }

    private static function revokeMembershipHandler(ContainerInterface $container): RevokeMembershipHandler
    {
        $handler = $container->get(RevokeMembershipHandler::class);

        if (!$handler instanceof RevokeMembershipHandler) {
            throw new LogicException('Revoke membership handler service is invalid.');
        }

        return $handler;
    }

    private static function membershipRepository(ContainerInterface $container): MembershipRepositoryInterface
    {
        $repository = $container->get(MembershipRepositoryInterface::class);

        if (!$repository instanceof MembershipRepositoryInterface) {
            throw new LogicException('Membership repository service is invalid.');
        }

        return $repository;
    }

    private static function operatorRepository(ContainerInterface $container): OperatorRepositoryInterface
    {
        $repository = $container->get(OperatorRepositoryInterface::class);

        if (!$repository instanceof OperatorRepositoryInterface) {
            throw new LogicException('Operator repository service is invalid.');
        }

        return $repository;
    }

    private static function listOrganizationMembershipsUseCase(ContainerInterface $container): ListOrganizationMembershipsUseCaseInterface
    {
        $useCase = $container->get(ListOrganizationMembershipsUseCaseInterface::class);

        if (!$useCase instanceof ListOrganizationMembershipsUseCaseInterface) {
            throw new LogicException('List organization memberships use case service is invalid.');
        }

        return $useCase;
    }

    private static function listOrganizationMembershipsHandler(ContainerInterface $container): ListOrganizationMembershipsHandler
    {
        $handler = $container->get(ListOrganizationMembershipsHandler::class);

        if (!$handler instanceof ListOrganizationMembershipsHandler) {
            throw new LogicException('List organization memberships handler service is invalid.');
        }

        return $handler;
    }
}
