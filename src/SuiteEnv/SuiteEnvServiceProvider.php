<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Psr\Container\ContainerInterface;

final readonly class SuiteEnvServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SuiteAppUrlReaderInterface::class,
                static fn (ContainerInterface $container): SuiteAppUrlReaderInterface => new EnvSuiteAppUrlReader(),
            )
            ->set(
                SuiteAppMachineKeyReaderInterface::class,
                static fn (ContainerInterface $container): SuiteAppMachineKeyReaderInterface => new EnvSuiteAppMachineKeyReader(),
            )
            ->set(
                SuiteEnvConfigFactory::class,
                static fn (ContainerInterface $container): SuiteEnvConfigFactory => new SuiteEnvConfigFactory(),
            )
            ->set(
                SuiteEnvWriterInterface::class,
                static function (ContainerInterface $container): SuiteEnvWriterInterface {
                    $path = self::env('NENE_SUITE_ENV_FILE_PATH', '');

                    if ($path === '') {
                        throw new LogicException('NENE_SUITE_ENV_FILE_PATH must be set to use WriteEnvConfigUseCase.');
                    }

                    return new FilePathSuiteEnvWriter($path);
                },
            )
            ->set(
                WriteEnvConfigUseCaseInterface::class,
                static function (ContainerInterface $container): WriteEnvConfigUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $factory = $container->get(SuiteEnvConfigFactory::class);
                    $writer = $container->get(SuiteEnvWriterInterface::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);
                    $urls = $container->get(SuiteAppUrlReaderInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);
                    $baseUrl = $container->get(RuntimeServiceProvider::SUITE_BASE_URL);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$factory instanceof SuiteEnvConfigFactory) {
                        throw new LogicException('Suite env config factory service is invalid.');
                    }

                    if (!$writer instanceof SuiteEnvWriterInterface) {
                        throw new LogicException('Suite env writer service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    if (!$urls instanceof SuiteAppUrlReaderInterface) {
                        throw new LogicException('Suite app URL reader service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    if (!is_string($baseUrl) || $baseUrl === '') {
                        throw new LogicException('Suite base URL service is invalid.');
                    }

                    return new WriteEnvConfigUseCase($sessions, $factory, $writer, $audit, $urls, $suiteId, $baseUrl);
                },
            );
    }

    private static function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
