<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
}
