<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
                DatabaseProvisionerInterface::class,
                static function (ContainerInterface $container): DatabaseProvisionerInterface {
                    $host = self::env('NENE_SUITE_PROVISION_DB_HOST', '127.0.0.1');
                    $port = self::env('NENE_SUITE_PROVISION_DB_PORT', '3306');
                    $user = self::env('NENE_SUITE_PROVISION_DB_USER', 'root');
                    $pass = self::env('NENE_SUITE_PROVISION_DB_PASSWORD', '');

                    if ($user === 'root' && $pass === '' && $host === '127.0.0.1') {
                        throw new LogicException(
                            'NENE_SUITE_PROVISION_DB_USER and NENE_SUITE_PROVISION_DB_PASSWORD must be set to use ProvisionAppDatabasesUseCase.',
                        );
                    }

                    try {
                        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
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
                    $namer = $container->get(AppDatabaseNamer::class);
                    $provisioner = $container->get(DatabaseProvisionerInterface::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$namer instanceof AppDatabaseNamer) {
                        throw new LogicException('App database namer service is invalid.');
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

                    return new ProvisionAppDatabasesUseCase($sessions, $namer, $provisioner, $audit, $suiteId);
                },
            );
    }

    private static function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
