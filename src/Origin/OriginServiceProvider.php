<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Origin client foundation (O0): config resolution + the outbound HTTP seam.
 * No routes or domain exceptions yet (the read API lands in O4), so this provider
 * is registered in ApplicationServiceProvider but contributes no registrar/handler.
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
            );
    }
}
