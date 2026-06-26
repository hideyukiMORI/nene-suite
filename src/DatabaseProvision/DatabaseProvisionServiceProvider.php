<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\Http\ControlDatabaseConfigResolver;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use PDO;
use PDOException;
use Psr\Container\ContainerInterface;

final readonly class DatabaseProvisionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AppDatabaseNamer::class,
                static fn (ContainerInterface $container): AppDatabaseNamer => new AppDatabaseNamer(),
            )
            ->set(
                DatabaseTargetResolverInterface::class,
                static function (ContainerInterface $container): DatabaseTargetResolverInterface {
                    $namer = $container->get(AppDatabaseNamer::class);

                    if (!$namer instanceof AppDatabaseNamer) {
                        throw new LogicException('App database namer service is invalid.');
                    }

                    return new EnvDatabaseTargetResolver($namer);
                },
            )
            ->set(
                DatabaseProvisionerInterface::class,
                static function (ContainerInterface $container): DatabaseProvisionerInterface {
                    // Provision with the same engine as the control DB (ADR 0016): derive
                    // it from the control URL scheme so MySQL/PostgreSQL stay in sync.
                    $engine = ControlDatabaseConfigResolver::controlAdapter() ?? 'mysql';
                    $isPgsql = $engine === 'pgsql';

                    $host = self::env('NENE_SUITE_PROVISION_DB_HOST', '127.0.0.1');
                    $port = self::env('NENE_SUITE_PROVISION_DB_PORT', $isPgsql ? '5432' : '3306');
                    $defaultUser = $isPgsql ? 'postgres' : 'root';
                    $user = self::env('NENE_SUITE_PROVISION_DB_USER', $defaultUser);
                    $pass = self::env('NENE_SUITE_PROVISION_DB_PASSWORD', '');

                    if ($user === $defaultUser && $pass === '' && $host === '127.0.0.1') {
                        throw new LogicException(
                            'NENE_SUITE_PROVISION_DB_USER and NENE_SUITE_PROVISION_DB_PASSWORD must be set to use ProvisionAppDatabasesUseCase.',
                        );
                    }

                    try {
                        if ($isPgsql) {
                            // PostgreSQL requires connecting to an existing maintenance
                            // database before issuing CREATE DATABASE for each app DB.
                            $maintenanceDb = self::env('NENE_SUITE_PROVISION_DB_NAME', 'postgres');
                            $dsn = "pgsql:host={$host};port={$port};dbname={$maintenanceDb}";
                        } else {
                            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                        }
                        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } catch (PDOException $e) {
                        throw new LogicException("Failed to connect to provisioning database: {$e->getMessage()}", 0, $e);
                    }

                    return new PdoDatabaseProvisioner($pdo);
                },
            )
            ->set(
                ProvisionAppDatabasesUseCaseInterface::class,
                static function (ContainerInterface $container): ProvisionAppDatabasesUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $targets = $container->get(DatabaseTargetResolverInterface::class);
                    $provisioner = $container->get(DatabaseProvisionerInterface::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$targets instanceof DatabaseTargetResolverInterface) {
                        throw new LogicException('Database target resolver service is invalid.');
                    }

                    if (!$provisioner instanceof DatabaseProvisionerInterface) {
                        throw new LogicException('Database provisioner service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new ProvisionAppDatabasesUseCase($sessions, $targets, $provisioner, $audit, $suiteId);
                },
            );
    }

    private static function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
