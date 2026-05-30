<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

use LogicException;
use Nene2\Config\AppConfig;
use Nene2\Config\ConfigLoader;
use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use NeNeSuite\ApplicationServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal apex runtime wiring for the Phase 1 read-only slice (catalog).
 *
 * Deliberately omits database, auth, and config services — those are added with
 * the install-session / audit slice. The NENE2 RuntimeApplicationFactory ships
 * `/` and `/health` itself, so this provider only registers PSR-17, the JSON
 * response factory, and the suite route registrars.
 */
final readonly class RuntimeServiceProvider implements ServiceProviderInterface
{
    public const PROJECT_ROOT = 'nene-suite.project_root';

    public const SUITE_ID = 'nene-suite.suite_id';

    /** Stable placeholder suite id used until the installer writes NENE_SUITE_ID. */
    private const DEV_SUITE_ID = '01J8XRDEV000000000000000ZA';

    /** Suite Problem Details base (docs/explanation/terminology.md §13). */
    private const PROBLEM_DETAILS_BASE_URL = 'https://nene-suite.dev/problems/';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new ApplicationServiceProvider());

        $builder
            ->set(
                self::SUITE_ID,
                static function (ContainerInterface $container): string {
                    $fromEnv = getenv('NENE_SUITE_ID');

                    return is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : self::DEV_SUITE_ID;
                },
            )
            ->set(
                ConfigLoader::class,
                static function (ContainerInterface $container): ConfigLoader {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new ConfigLoader($projectRoot);
                },
            )
            ->set(
                AppConfig::class,
                static function (ContainerInterface $container): AppConfig {
                    $loader = $container->get(ConfigLoader::class);

                    if (!$loader instanceof ConfigLoader) {
                        throw new LogicException('Config loader service is invalid.');
                    }

                    return $loader->load();
                },
            )
            ->set(
                DatabaseConnectionFactoryInterface::class,
                static function (ContainerInterface $container): DatabaseConnectionFactoryInterface {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new PdoConnectionFactory($config->database);
                },
            )
            ->set(
                DatabaseQueryExecutorInterface::class,
                static function (ContainerInterface $container): DatabaseQueryExecutorInterface {
                    $connectionFactory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connectionFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseQueryExecutor($connectionFactory);
                },
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $container): ProblemDetailsResponseFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    // Problem Details `type` MUST use the suite base (terminology §13);
                    // honour an explicit PROBLEM_DETAILS_BASE_URL override, else default
                    // to the suite base rather than the NENE2 framework default.
                    $override = $_SERVER['PROBLEM_DETAILS_BASE_URL'] ?? $_ENV['PROBLEM_DETAILS_BASE_URL'] ?? null;
                    $baseUrl = is_string($override) && $override !== '' ? $override : self::PROBLEM_DETAILS_BASE_URL;

                    return new ProblemDetailsResponseFactory($responseFactory, $streamFactory, $baseUrl);
                },
            )
            ->set(Psr17Factory::class, static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory())
            ->set(
                ResponseFactoryInterface::class,
                static function (ContainerInterface $container): ResponseFactoryInterface {
                    $factory = $container->get(Psr17Factory::class);

                    if (!$factory instanceof ResponseFactoryInterface) {
                        throw new LogicException('PSR-17 response factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                StreamFactoryInterface::class,
                static function (ContainerInterface $container): StreamFactoryInterface {
                    $factory = $container->get(Psr17Factory::class);

                    if (!$factory instanceof StreamFactoryInterface) {
                        throw new LogicException('PSR-17 stream factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                JsonResponseFactory::class,
                static function (ContainerInterface $container): JsonResponseFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new JsonResponseFactory($responseFactory, $streamFactory);
                },
            )
            ->set(RequestIdHolder::class, static fn (ContainerInterface $container): RequestIdHolder => new RequestIdHolder())
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);
                    $exceptionHandlers = $container->get(ApplicationServiceProvider::EXCEPTION_HANDLERS);
                    $routeRegistrars = $container->get(ApplicationServiceProvider::ROUTE_REGISTRARS);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    if (!is_array($exceptionHandlers) || !array_is_list($exceptionHandlers)) {
                        throw new LogicException('Exception handlers service is invalid.');
                    }

                    if (!is_array($routeRegistrars) || !array_is_list($routeRegistrars)) {
                        throw new LogicException('Route registrars service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $exceptionHandlers */
                    /** @var list<callable(Router): void> $routeRegistrars */

                    return new RuntimeApplicationFactory(
                        responseFactory: $responseFactory,
                        streamFactory: $streamFactory,
                        domainExceptionHandlers: $exceptionHandlers,
                        requestIdHolder: $requestIdHolder,
                        routeRegistrars: $routeRegistrars,
                    );
                },
            )
            ->set(
                RequestHandlerInterface::class,
                static function (ContainerInterface $container): RequestHandlerInterface {
                    $factory = $container->get(RuntimeApplicationFactory::class);

                    if (!$factory instanceof RuntimeApplicationFactory) {
                        throw new LogicException('Runtime application factory service is invalid.');
                    }

                    return $factory->create();
                },
            )
            ->set(ResponseEmitter::class, static fn (ContainerInterface $container): ResponseEmitter => new ResponseEmitter());
    }
}
