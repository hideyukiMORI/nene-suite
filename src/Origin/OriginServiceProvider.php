<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCaseInterface;
use NeNeSuite\SiblingHealth\InstalledVersionResolverInterface;
use Psr\Container\ContainerInterface;

/**
 * Origin client services: config resolution + the outbound HTTP seam (O0), the JWS signature
 * primitive (O1a), the gen watermark store (O2), the mirror-ordered object-store provider, the
 * verifier + fetch/aggregate engines (O1b/O3), and the read API (O4 — the updates endpoint,
 * operator-authenticated).
 */
final readonly class OriginServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OriginSignatureVerifier::class,
                static fn (ContainerInterface $container): OriginSignatureVerifier => new OriginSignatureVerifier(),
            )
            ->set(
                OriginClientConfigResolver::class,
                static fn (ContainerInterface $container): OriginClientConfigResolver => new OriginClientConfigResolver(),
            )
            ->set(
                OriginClientConfig::class,
                static function (ContainerInterface $container): OriginClientConfig {
                    $resolver = $container->get(OriginClientConfigResolver::class);

                    if (!$resolver instanceof OriginClientConfigResolver) {
                        throw new LogicException('Origin client config resolver service is invalid.');
                    }

                    return $resolver->resolve();
                },
            )
            ->set(
                OriginHttpClientInterface::class,
                static function (ContainerInterface $container): OriginHttpClientInterface {
                    $config = $container->get(OriginClientConfig::class);

                    if (!$config instanceof OriginClientConfig) {
                        throw new LogicException('Origin client config service is invalid.');
                    }

                    return new StreamOriginHttpClient($config->timeoutSeconds);
                },
            )
            ->set(
                OriginGenWatermarkRepositoryInterface::class,
                static function (ContainerInterface $container): OriginGenWatermarkRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoOriginGenWatermarkRepository($query);
                },
            )
            ->set(
                OriginReadModelVerifier::class,
                static fn (ContainerInterface $container): OriginReadModelVerifier => new OriginReadModelVerifier(),
            )
            ->set(
                OriginObjectStoreProvider::class,
                static function (ContainerInterface $container): OriginObjectStoreProvider {
                    $client = $container->get(OriginHttpClientInterface::class);
                    $config = $container->get(OriginClientConfig::class);

                    if (!$client instanceof OriginHttpClientInterface) {
                        throw new LogicException('Origin HTTP client service is invalid.');
                    }

                    if (!$config instanceof OriginClientConfig) {
                        throw new LogicException('Origin client config service is invalid.');
                    }

                    return new HttpOriginObjectStoreProvider($client, $config->mirrors);
                },
            )
            ->set(
                OriginUpdateAggregator::class,
                static function (ContainerInterface $container): OriginUpdateAggregator {
                    $verifier = $container->get(OriginReadModelVerifier::class);

                    if (!$verifier instanceof OriginReadModelVerifier) {
                        throw new LogicException('Origin read model verifier service is invalid.');
                    }

                    return new OriginUpdateAggregator($verifier);
                },
            )
            ->set(
                OriginFeedReader::class,
                static function (ContainerInterface $container): OriginFeedReader {
                    $verifier = $container->get(OriginReadModelVerifier::class);

                    if (!$verifier instanceof OriginReadModelVerifier) {
                        throw new LogicException('Origin read model verifier service is invalid.');
                    }

                    return new OriginFeedReader($verifier);
                },
            )
            ->set(
                OriginTrustAnchorProvider::class,
                static function (ContainerInterface $container): OriginTrustAnchorProvider {
                    $config = $container->get(OriginClientConfig::class);

                    if (!$config instanceof OriginClientConfig) {
                        throw new LogicException('Origin client config service is invalid.');
                    }

                    return new OriginTrustAnchorProvider($config->trustAnchorPath);
                },
            )
            ->set(
                GetOriginUpdatesUseCase::class,
                static function (ContainerInterface $container): GetOriginUpdatesUseCase {
                    $config = $container->get(OriginClientConfig::class);
                    $anchors = $container->get(OriginTrustAnchorProvider::class);
                    $installed = $container->get(ListInstalledAppsUseCaseInterface::class);
                    $versions = $container->get(InstalledVersionResolverInterface::class);
                    $stores = $container->get(OriginObjectStoreProvider::class);
                    $aggregator = $container->get(OriginUpdateAggregator::class);
                    $watermarks = $container->get(OriginGenWatermarkRepositoryInterface::class);

                    if (!$config instanceof OriginClientConfig) {
                        throw new LogicException('Origin client config service is invalid.');
                    }

                    if (!$anchors instanceof OriginTrustAnchorProvider) {
                        throw new LogicException('Origin trust anchor provider service is invalid.');
                    }

                    if (!$installed instanceof ListInstalledAppsUseCaseInterface) {
                        throw new LogicException('Installed apps use case service is invalid.');
                    }

                    if (!$versions instanceof InstalledVersionResolverInterface) {
                        throw new LogicException('Installed version resolver service is invalid.');
                    }

                    if (!$stores instanceof OriginObjectStoreProvider) {
                        throw new LogicException('Origin object store provider service is invalid.');
                    }

                    if (!$aggregator instanceof OriginUpdateAggregator) {
                        throw new LogicException('Origin update aggregator service is invalid.');
                    }

                    if (!$watermarks instanceof OriginGenWatermarkRepositoryInterface) {
                        throw new LogicException('Origin gen watermark repository service is invalid.');
                    }

                    return new GetOriginUpdatesUseCase($config, $anchors, $installed, $versions, $stores, $aggregator, $watermarks);
                },
            )
            ->set(
                GetOriginUpdatesUseCaseInterface::class,
                static function (ContainerInterface $container): GetOriginUpdatesUseCaseInterface {
                    $useCase = $container->get(GetOriginUpdatesUseCase::class);

                    if (!$useCase instanceof GetOriginUpdatesUseCase) {
                        throw new LogicException('Get Origin updates use case service is invalid.');
                    }

                    return $useCase;
                },
            )
            ->set(
                GetOriginUpdatesHandler::class,
                static function (ContainerInterface $container): GetOriginUpdatesHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(GetOriginUpdatesUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $clock = $container->get(ClockInterface::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof GetOriginUpdatesUseCase) {
                        throw new LogicException('Get Origin updates use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new GetOriginUpdatesHandler($authenticator, $useCase, $response, $clock);
                },
            )
            ->set(
                GetOriginFeedsUseCase::class,
                static function (ContainerInterface $container): GetOriginFeedsUseCase {
                    $config = $container->get(OriginClientConfig::class);
                    $anchors = $container->get(OriginTrustAnchorProvider::class);
                    $installed = $container->get(ListInstalledAppsUseCaseInterface::class);
                    $stores = $container->get(OriginObjectStoreProvider::class);
                    $reader = $container->get(OriginFeedReader::class);
                    $watermarks = $container->get(OriginGenWatermarkRepositoryInterface::class);

                    if (!$config instanceof OriginClientConfig) {
                        throw new LogicException('Origin client config service is invalid.');
                    }

                    if (!$anchors instanceof OriginTrustAnchorProvider) {
                        throw new LogicException('Origin trust anchor provider service is invalid.');
                    }

                    if (!$installed instanceof ListInstalledAppsUseCaseInterface) {
                        throw new LogicException('Installed apps use case service is invalid.');
                    }

                    if (!$stores instanceof OriginObjectStoreProvider) {
                        throw new LogicException('Origin object store provider service is invalid.');
                    }

                    if (!$reader instanceof OriginFeedReader) {
                        throw new LogicException('Origin feed reader service is invalid.');
                    }

                    if (!$watermarks instanceof OriginGenWatermarkRepositoryInterface) {
                        throw new LogicException('Origin gen watermark repository service is invalid.');
                    }

                    return new GetOriginFeedsUseCase($config, $anchors, $installed, $stores, $reader, $watermarks);
                },
            )
            ->set(
                GetOriginFeedsHandler::class,
                static function (ContainerInterface $container): GetOriginFeedsHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(GetOriginFeedsUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $clock = $container->get(ClockInterface::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof GetOriginFeedsUseCase) {
                        throw new LogicException('Get Origin feeds use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new GetOriginFeedsHandler($authenticator, $useCase, $response, $clock);
                },
            )
            ->set(
                'nene-suite.route_registrar.origin',
                static function (ContainerInterface $container): OriginRouteRegistrar {
                    $updatesHandler = $container->get(GetOriginUpdatesHandler::class);
                    $feedsHandler = $container->get(GetOriginFeedsHandler::class);

                    if (!$updatesHandler instanceof GetOriginUpdatesHandler) {
                        throw new LogicException('Get Origin updates handler service is invalid.');
                    }

                    if (!$feedsHandler instanceof GetOriginFeedsHandler) {
                        throw new LogicException('Get Origin feeds handler service is invalid.');
                    }

                    return new OriginRouteRegistrar($updatesHandler, $feedsHandler);
                },
            );
    }
}
