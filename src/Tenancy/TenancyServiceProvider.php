<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
}
