<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Container\ContainerInterface;

final readonly class SuiteAuditServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(SuiteAuditSanitizer::class, static fn (ContainerInterface $container): SuiteAuditSanitizer => new SuiteAuditSanitizer())
            ->set(
                SuiteAuditRecorderInterface::class,
                static function (ContainerInterface $container): SuiteAuditRecorderInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $sanitizer = $container->get(SuiteAuditSanitizer::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$sanitizer instanceof SuiteAuditSanitizer) {
                        throw new LogicException('Suite audit sanitizer service is invalid.');
                    }

                    return new PdoSuiteAuditRecorder($query, $sanitizer);
                },
            )
            ->set(
                SuiteAuditEventReaderInterface::class,
                static function (ContainerInterface $container): SuiteAuditEventReaderInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoSuiteAuditEventReader($query);
                },
            )
            ->set(
                ListSuiteAuditEventsUseCaseInterface::class,
                static function (ContainerInterface $container): ListSuiteAuditEventsUseCaseInterface {
                    $reader = $container->get(SuiteAuditEventReaderInterface::class);

                    if (!$reader instanceof SuiteAuditEventReaderInterface) {
                        throw new LogicException('Suite audit event reader service is invalid.');
                    }

                    return new ListSuiteAuditEventsUseCase($reader);
                },
            )
            ->set(
                ListSuiteAuditEventsHandler::class,
                static function (ContainerInterface $container): ListSuiteAuditEventsHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(ListSuiteAuditEventsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof ListSuiteAuditEventsUseCaseInterface) {
                        throw new LogicException('ListSuiteAuditEvents use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListSuiteAuditEventsHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                'nene-suite.route_registrar.suite_audit',
                static function (ContainerInterface $container): SuiteAuditRouteRegistrar {
                    $listHandler = $container->get(ListSuiteAuditEventsHandler::class);

                    if (!$listHandler instanceof ListSuiteAuditEventsHandler) {
                        throw new LogicException('ListSuiteAuditEvents handler service is invalid.');
                    }

                    return new SuiteAuditRouteRegistrar($listHandler);
                },
            );
    }
}
