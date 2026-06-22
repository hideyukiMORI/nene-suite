<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use NeNeSuite\Tenancy\GrantMembershipUseCaseInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use NeNeSuite\Tenancy\SuperadminGuard;
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
                OperatorRepositoryFactoryInterface::class,
                static fn (ContainerInterface $container): OperatorRepositoryFactoryInterface => new PdoOperatorRepositoryFactory(),
            )
            ->set(
                LoginAttemptRepositoryInterface::class,
                static function (ContainerInterface $container): LoginAttemptRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoLoginAttemptRepository($query);
                },
            )
            ->set(
                LoginRateLimiter::class,
                static function (ContainerInterface $container): LoginRateLimiter {
                    $attempts = $container->get(LoginAttemptRepositoryInterface::class);

                    if (!$attempts instanceof LoginAttemptRepositoryInterface) {
                        throw new LogicException('Login attempt repository service is invalid.');
                    }

                    return new LoginRateLimiter($attempts);
                },
            )
            ->set(
                ClientIpResolver::class,
                static function (ContainerInterface $container): ClientIpResolver {
                    $raw = $_SERVER['NENE_SUITE_TRUSTED_PROXIES'] ?? $_ENV['NENE_SUITE_TRUSTED_PROXIES'] ?? '';

                    return new ClientIpResolver(ClientIpResolver::parseTrustedProxies(is_string($raw) ? $raw : ''));
                },
            )
            ->set(
                RevokedTokenRepositoryInterface::class,
                static function (ContainerInterface $container): RevokedTokenRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoRevokedTokenRepository($query);
                },
            )
            ->set(
                OperatorSessionContextResolver::class,
                static function (ContainerInterface $container): OperatorSessionContextResolver {
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);

                    if (!$memberships instanceof MembershipRepositoryInterface) {
                        throw new LogicException('Membership repository service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    return new OperatorSessionContextResolver($memberships, $organizations);
                },
            )
            ->set(
                BearerTokenAuthenticator::class,
                static function (ContainerInterface $container): BearerTokenAuthenticator {
                    $verifier = $container->get(TokenVerifierInterface::class);
                    $resolver = $container->get(OperatorSessionContextResolver::class);
                    $revokedTokens = $container->get(RevokedTokenRepositoryInterface::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    if (!$resolver instanceof OperatorSessionContextResolver) {
                        throw new LogicException('Operator session context resolver service is invalid.');
                    }

                    if (!$revokedTokens instanceof RevokedTokenRepositoryInterface) {
                        throw new LogicException('Revoked token repository service is invalid.');
                    }

                    return new BearerTokenAuthenticator($verifier, $resolver, $revokedTokens);
                },
            )
            ->set(
                CreateAuthSessionUseCaseInterface::class,
                static function (ContainerInterface $container): CreateAuthSessionUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);
                    $hasher = $container->get(PasswordHasher::class);
                    $issuer = $container->get(TokenIssuerInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);
                    $sessionContext = $container->get(OperatorSessionContextResolver::class);
                    $rateLimiter = $container->get(LoginRateLimiter::class);

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

                    if (!$sessionContext instanceof OperatorSessionContextResolver) {
                        throw new LogicException('Operator session context resolver service is invalid.');
                    }

                    if (!$rateLimiter instanceof LoginRateLimiter) {
                        throw new LogicException('Login rate limiter service is invalid.');
                    }

                    return new CreateAuthSessionUseCase($operators, $hasher, $issuer, $suiteId, $sessionContext, $rateLimiter);
                },
            )
            ->set(
                CreateAuthSessionHandler::class,
                static function (ContainerInterface $container): CreateAuthSessionHandler {
                    $useCase = $container->get(CreateAuthSessionUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $clientIpResolver = $container->get(ClientIpResolver::class);

                    if (!$useCase instanceof CreateAuthSessionUseCaseInterface) {
                        throw new LogicException('CreateAuthSession use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$clientIpResolver instanceof ClientIpResolver) {
                        throw new LogicException('Client IP resolver service is invalid.');
                    }

                    return new CreateAuthSessionHandler($useCase, $response, $clientIpResolver);
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
                ListOperatorsUseCaseInterface::class,
                static function (ContainerInterface $container): ListOperatorsUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    return new ListOperatorsUseCase($operators);
                },
            )
            ->set(
                ListSessionOrganizationsUseCaseInterface::class,
                static function (ContainerInterface $container): ListSessionOrganizationsUseCaseInterface {
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);

                    if (!$memberships instanceof MembershipRepositoryInterface) {
                        throw new LogicException('Membership repository service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    return new ListSessionOrganizationsUseCase($memberships, $organizations);
                },
            )
            ->set(
                SwitchActiveOrganizationUseCaseInterface::class,
                static function (ContainerInterface $container): SwitchActiveOrganizationUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $issuer = $container->get(TokenIssuerInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    if (!$memberships instanceof MembershipRepositoryInterface) {
                        throw new LogicException('Membership repository service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$issuer instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new SwitchActiveOrganizationUseCase($operators, $memberships, $organizations, $issuer, $suiteId);
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
                    $revokedTokens = $container->get(RevokedTokenRepositoryInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$revokedTokens instanceof RevokedTokenRepositoryInterface) {
                        throw new LogicException('Revoked token repository service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteAuthSessionHandler($authenticator, $revokedTokens, $response);
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
                LoginRateLimitedExceptionHandler::class,
                static function (ContainerInterface $container): LoginRateLimitedExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new LoginRateLimitedExceptionHandler($problemDetails);
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
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);
                    $operators = $container->get(OperatorRepositoryFactoryInterface::class);
                    $audit = $container->get(SuiteAuditRecorderFactoryInterface::class);
                    $hasher = $container->get(PasswordHasher::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$operators instanceof OperatorRepositoryFactoryInterface) {
                        throw new LogicException('Operator repository factory service is invalid.');
                    }

                    if (!$audit instanceof SuiteAuditRecorderFactoryInterface) {
                        throw new LogicException('Suite audit recorder factory service is invalid.');
                    }

                    if (!$hasher instanceof PasswordHasher) {
                        throw new LogicException('Password hasher service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new CreateOperatorUseCase($transactions, $operators, $audit, $hasher, $suiteId);
                },
            )
            ->set(
                ProvisionApexOperatorUseCaseInterface::class,
                static function (ContainerInterface $container): ProvisionApexOperatorUseCaseInterface {
                    $createOperator = $container->get(CreateOperatorUseCaseInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $grantMembership = $container->get(GrantMembershipUseCaseInterface::class);
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $orgExternalId = $container->get(RuntimeServiceProvider::SUITE_ORG_EXTERNAL_ID);

                    if (!$createOperator instanceof CreateOperatorUseCaseInterface) {
                        throw new LogicException('CreateOperator use case service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$grantMembership instanceof GrantMembershipUseCaseInterface) {
                        throw new LogicException('GrantMembership use case service is invalid.');
                    }

                    if (!$memberships instanceof MembershipRepositoryInterface) {
                        throw new LogicException('Membership repository service is invalid.');
                    }

                    if (!is_string($orgExternalId) || $orgExternalId === '') {
                        throw new LogicException('Suite org external id service is invalid.');
                    }

                    return new ProvisionApexOperatorUseCase($createOperator, $organizations, $grantMembership, $memberships, $orgExternalId);
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
                    $useCase = $container->get(ProvisionApexOperatorUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof ProvisionApexOperatorUseCaseInterface) {
                        throw new LogicException('ProvisionApexOperator use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateOperatorHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                ListOperatorsHandler::class,
                static function (ContainerInterface $container): ListOperatorsHandler {
                    $guard = $container->get(SuperadminGuard::class);
                    $useCase = $container->get(ListOperatorsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$guard instanceof SuperadminGuard) {
                        throw new LogicException('Superadmin guard service is invalid.');
                    }

                    if (!$useCase instanceof ListOperatorsUseCaseInterface) {
                        throw new LogicException('ListOperators use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListOperatorsHandler($guard, $useCase, $response);
                },
            )
            ->set(
                ListSessionOrganizationsHandler::class,
                static function (ContainerInterface $container): ListSessionOrganizationsHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(ListSessionOrganizationsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof ListSessionOrganizationsUseCaseInterface) {
                        throw new LogicException('ListSessionOrganizations use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListSessionOrganizationsHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                SwitchActiveOrganizationHandler::class,
                static function (ContainerInterface $container): SwitchActiveOrganizationHandler {
                    $authenticator = $container->get(BearerTokenAuthenticator::class);
                    $useCase = $container->get(SwitchActiveOrganizationUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$authenticator instanceof BearerTokenAuthenticator) {
                        throw new LogicException('Bearer token authenticator service is invalid.');
                    }

                    if (!$useCase instanceof SwitchActiveOrganizationUseCaseInterface) {
                        throw new LogicException('SwitchActiveOrganization use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new SwitchActiveOrganizationHandler($authenticator, $useCase, $response);
                },
            )
            ->set(
                'nene-suite.route_registrar.auth',
                static function (ContainerInterface $container): AuthRouteRegistrar {
                    $create = $container->get(CreateAuthSessionHandler::class);
                    $get = $container->get(GetAuthSessionHandler::class);
                    $delete = $container->get(DeleteAuthSessionHandler::class);
                    $createOperator = $container->get(CreateOperatorHandler::class);
                    $listOperators = $container->get(ListOperatorsHandler::class);
                    $listSessionOrganizations = $container->get(ListSessionOrganizationsHandler::class);
                    $switchActiveOrganization = $container->get(SwitchActiveOrganizationHandler::class);

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

                    if (!$listOperators instanceof ListOperatorsHandler) {
                        throw new LogicException('ListOperators handler service is invalid.');
                    }

                    if (!$listSessionOrganizations instanceof ListSessionOrganizationsHandler) {
                        throw new LogicException('ListSessionOrganizations handler service is invalid.');
                    }

                    if (!$switchActiveOrganization instanceof SwitchActiveOrganizationHandler) {
                        throw new LogicException('SwitchActiveOrganization handler service is invalid.');
                    }

                    return new AuthRouteRegistrar(
                        $create,
                        $get,
                        $delete,
                        $createOperator,
                        $listOperators,
                        $listSessionOrganizations,
                        $switchActiveOrganization,
                    );
                },
            );
    }
}
