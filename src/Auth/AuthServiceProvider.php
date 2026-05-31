<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use Psr\Container\ContainerInterface;

final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(PasswordHasher::class, static fn (ContainerInterface $container): PasswordHasher => new PasswordHasher())
            ->set(
                OperatorRepositoryInterface::class,
                static function (ContainerInterface $container): OperatorRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoOperatorRepository($query);
                },
            )
            ->set(
                BearerTokenAuthenticator::class,
                static function (ContainerInterface $container): BearerTokenAuthenticator {
                    $verifier = $container->get(TokenVerifierInterface::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    return new BearerTokenAuthenticator($verifier);
                },
            )
            ->set(
                CreateAuthSessionUseCaseInterface::class,
                static function (ContainerInterface $container): CreateAuthSessionUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);
                    $hasher = $container->get(PasswordHasher::class);
                    $issuer = $container->get(TokenIssuerInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    if (!$hasher instanceof PasswordHasher) {
                        throw new LogicException('Password hasher service is invalid.');
                    }

                    if (!$issuer instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new CreateAuthSessionUseCase($operators, $hasher, $issuer, $suiteId);
                },
            )
            ->set(
                CreateAuthSessionHandler::class,
                static function (ContainerInterface $container): CreateAuthSessionHandler {
                    $useCase = $container->get(CreateAuthSessionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateAuthSessionUseCaseInterface) {
                        throw new LogicException('CreateAuthSession use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateAuthSessionHandler($useCase, $response);
                },
            )
            ->set(
                GetAuthSessionUseCaseInterface::class,
                static function (ContainerInterface $container): GetAuthSessionUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    return new GetAuthSessionUseCase($operators);
                },
            )
            ->set(
                GetAuthSessionHandler::class,
                static function (ContainerInterface $container): GetAuthSessionHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(GetAuthSessionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof GetAuthSessionUseCaseInterface) {
                        throw new LogicException('GetAuthSession use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetAuthSessionHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                DeleteAuthSessionHandler::class,
                static function (ContainerInterface $container): DeleteAuthSessionHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteAuthSessionHandler($authenticator, $response);
                },
            )
            ->set(
                InvalidCredentialsExceptionHandler::class,
                static function (ContainerInterface $container): InvalidCredentialsExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new InvalidCredentialsExceptionHandler($problemDetails);
                },
            )
            ->set(
                UnauthorizedExceptionHandler::class,
                static function (ContainerInterface $container): UnauthorizedExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new UnauthorizedExceptionHandler($problemDetails);
                },
            )
            ->set(
                CreateOperatorUseCaseInterface::class,
                static function (ContainerInterface $container): CreateOperatorUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);
                    $hasher = $container->get(PasswordHasher::class);
                    $audit = $container->get(SuiteAuditRecorderInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    if (!$hasher instanceof PasswordHasher) {
                        throw new LogicException('Password hasher service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderInterface) {
                        throw new LogicException('Suite audit recorder service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new CreateOperatorUseCase($operators, $hasher, $audit, $suiteId);
                },
            )
            ->set(
                OperatorEmailConflictExceptionHandler::class,
                static function (ContainerInterface $container): OperatorEmailConflictExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new OperatorEmailConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                OperatorValidationExceptionHandler::class,
                static function (ContainerInterface $container): OperatorValidationExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new OperatorValidationExceptionHandler($problemDetails);
                },
            )
            ->set(
                CreateOperatorHandler::class,
                static function (ContainerInterface $container): CreateOperatorHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(CreateOperatorUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof CreateOperatorUseCaseInterface) {
                        throw new LogicException('CreateOperator use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateOperatorHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                'nene-suite.route_registrar.auth',
                static function (ContainerInterface $container): AuthRouteRegistrar {
                    $create = $container->get(CreateAuthSessionHandler::class);
                    $get = $container->get(GetAuthSessionHandler::class);
                    $delete = $container->get(DeleteAuthSessionHandler::class);
                    $createOperator = $container->get(CreateOperatorHandler::class);

                    if (!$create instanceof CreateAuthSessionHandler) {
                        throw new LogicException('CreateAuthSession handler service is invalid.');
                    }

                    if (!$get instanceof GetAuthSessionHandler) {
                        throw new LogicException('GetAuthSession handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteAuthSessionHandler) {
                        throw new LogicException('DeleteAuthSession handler service is invalid.');
                    }

                    if (!$createOperator instanceof CreateOperatorHandler) {
                        throw new LogicException('CreateOperator handler service is invalid.');
                    }

                    return new AuthRouteRegistrar($create, $get, $delete, $createOperator);
                },
            );
    }
}
