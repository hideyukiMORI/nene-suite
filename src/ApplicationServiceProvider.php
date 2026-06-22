<?php

declare(strict_types=1);

namespace NeNeSuite;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeNeSuite\AppCatalog\AppCatalogRouteRegistrar;
use NeNeSuite\AppCatalog\AppCatalogServiceProvider;
use NeNeSuite\AppSelection\AppSelectionRouteRegistrar;
use NeNeSuite\AppSelection\AppSelectionServiceProvider;
use NeNeSuite\Auth\AuthRouteRegistrar;
use NeNeSuite\Auth\AuthServiceProvider;
use NeNeSuite\Auth\InvalidCredentialsExceptionHandler;
use NeNeSuite\Auth\LoginRateLimitedExceptionHandler;
use NeNeSuite\Auth\OperatorEmailConflictExceptionHandler;
use NeNeSuite\Auth\OperatorValidationExceptionHandler;
use NeNeSuite\Auth\UnauthorizedExceptionHandler;
use NeNeSuite\DatabaseProvision\DatabaseProvisionServiceProvider;
use NeNeSuite\InstalledApps\InstalledAppsRouteRegistrar;
use NeNeSuite\InstalledApps\InstalledAppsServiceProvider;
use NeNeSuite\Installer\InstallerServiceProvider;
use NeNeSuite\InstallManifest\InstallManifestServiceProvider;
use NeNeSuite\InstallSession\InstallSessionConflictExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionNotFoundExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionNotReadyExceptionHandler;
use NeNeSuite\InstallSession\InstallSessionRouteRegistrar;
use NeNeSuite\InstallSession\InstallSessionServiceProvider;
use NeNeSuite\SuiteAudit\SuiteAuditRouteRegistrar;
use NeNeSuite\SuiteAudit\SuiteAuditServiceProvider;
use NeNeSuite\SuiteEnv\SuiteEnvServiceProvider;
use NeNeSuite\Tenancy\ForbiddenExceptionHandler;
use NeNeSuite\Tenancy\MembershipConflictExceptionHandler;
use NeNeSuite\Tenancy\MembershipInvariantExceptionHandler;
use NeNeSuite\Tenancy\MembershipNotFoundExceptionHandler;
use NeNeSuite\Tenancy\MembershipRouteRegistrar;
use NeNeSuite\Tenancy\MembershipValidationExceptionHandler;
use NeNeSuite\Tenancy\OrganizationNotFoundExceptionHandler;
use NeNeSuite\Tenancy\OrganizationRouteRegistrar;
use NeNeSuite\Tenancy\OrganizationSlugConflictExceptionHandler;
use NeNeSuite\Tenancy\OrganizationValidationExceptionHandler;
use NeNeSuite\Tenancy\TenancyServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Aggregates suite orchestration domain providers and exposes the route
 * registrar / exception handler lists consumed by the runtime factory.
 * No business logic lives here.
 */
final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-suite.route_registrars';

    public const EXCEPTION_HANDLERS = 'nene-suite.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->addProvider(new AppCatalogServiceProvider())
            ->addProvider(new SuiteAuditServiceProvider())
            ->addProvider(new InstallManifestServiceProvider())
            ->addProvider(new InstallSessionServiceProvider())
            ->addProvider(new AppSelectionServiceProvider())
            ->addProvider(new AuthServiceProvider())
            ->addProvider(new SuiteEnvServiceProvider())
            ->addProvider(new DatabaseProvisionServiceProvider())
            ->addProvider(new InstalledAppsServiceProvider())
            ->addProvider(new InstallerServiceProvider())
            ->addProvider(new TenancyServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $appCatalog = $container->get('nene-suite.route_registrar.app_catalog');
                    $installSession = $container->get('nene-suite.route_registrar.install_session');
                    $appSelection = $container->get('nene-suite.route_registrar.app_selection');
                    $auth = $container->get('nene-suite.route_registrar.auth');
                    $suiteAudit = $container->get('nene-suite.route_registrar.suite_audit');
                    $installedApps = $container->get('nene-suite.route_registrar.installed_apps');
                    $tenancy = $container->get('nene-suite.route_registrar.tenancy');
                    $memberships = $container->get('nene-suite.route_registrar.memberships');

                    if (!$appCatalog instanceof AppCatalogRouteRegistrar) {
                        throw new LogicException('App catalog route registrar service is invalid.');
                    }

                    if (!$installSession instanceof InstallSessionRouteRegistrar) {
                        throw new LogicException('Install session route registrar service is invalid.');
                    }

                    if (!$appSelection instanceof AppSelectionRouteRegistrar) {
                        throw new LogicException('App selection route registrar service is invalid.');
                    }

                    if (!$auth instanceof AuthRouteRegistrar) {
                        throw new LogicException('Auth route registrar service is invalid.');
                    }

                    if (!$suiteAudit instanceof SuiteAuditRouteRegistrar) {
                        throw new LogicException('Suite audit route registrar service is invalid.');
                    }

                    if (!$installedApps instanceof InstalledAppsRouteRegistrar) {
                        throw new LogicException('Installed apps route registrar service is invalid.');
                    }

                    if (!$tenancy instanceof OrganizationRouteRegistrar) {
                        throw new LogicException('Tenancy route registrar service is invalid.');
                    }

                    if (!$memberships instanceof MembershipRouteRegistrar) {
                        throw new LogicException('Membership route registrar service is invalid.');
                    }

                    return [
                        $appCatalog,
                        $installSession,
                        $appSelection,
                        $auth,
                        $suiteAudit,
                        $installedApps,
                        $tenancy,
                        $memberships,
                    ];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $installSessionNotFound = $container->get(InstallSessionNotFoundExceptionHandler::class);
                    $installSessionConflict = $container->get(InstallSessionConflictExceptionHandler::class);
                    $installSessionNotReady = $container->get(InstallSessionNotReadyExceptionHandler::class);
                    $invalidCredentials = $container->get(InvalidCredentialsExceptionHandler::class);
                    $loginRateLimited = $container->get(LoginRateLimitedExceptionHandler::class);
                    $unauthorized = $container->get(UnauthorizedExceptionHandler::class);
                    $operatorEmailConflict = $container->get(OperatorEmailConflictExceptionHandler::class);
                    $operatorValidation = $container->get(OperatorValidationExceptionHandler::class);
                    $forbidden = $container->get(ForbiddenExceptionHandler::class);
                    $organizationValidation = $container->get(OrganizationValidationExceptionHandler::class);
                    $organizationSlugConflict = $container->get(OrganizationSlugConflictExceptionHandler::class);
                    $organizationNotFound = $container->get(OrganizationNotFoundExceptionHandler::class);
                    $membershipValidation = $container->get(MembershipValidationExceptionHandler::class);
                    $membershipConflict = $container->get(MembershipConflictExceptionHandler::class);
                    $membershipInvariant = $container->get(MembershipInvariantExceptionHandler::class);
                    $membershipNotFound = $container->get(MembershipNotFoundExceptionHandler::class);

                    if (!$installSessionNotFound instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session not found exception handler service is invalid.');
                    }

                    if (!$installSessionConflict instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session conflict exception handler service is invalid.');
                    }

                    if (!$installSessionNotReady instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Install session not ready exception handler service is invalid.');
                    }

                    if (!$invalidCredentials instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Invalid credentials exception handler service is invalid.');
                    }

                    if (!$loginRateLimited instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Login rate limited exception handler service is invalid.');
                    }

                    if (!$unauthorized instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Unauthorized exception handler service is invalid.');
                    }

                    if (!$operatorEmailConflict instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Operator email conflict exception handler service is invalid.');
                    }

                    if (!$operatorValidation instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Operator validation exception handler service is invalid.');
                    }

                    if (!$forbidden instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Forbidden exception handler service is invalid.');
                    }

                    if (!$organizationValidation instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Organization validation exception handler service is invalid.');
                    }

                    if (!$organizationSlugConflict instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Organization slug conflict exception handler service is invalid.');
                    }

                    if (!$organizationNotFound instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Organization not found exception handler service is invalid.');
                    }

                    if (!$membershipValidation instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Membership validation exception handler service is invalid.');
                    }

                    if (!$membershipConflict instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Membership conflict exception handler service is invalid.');
                    }

                    if (!$membershipInvariant instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Membership invariant exception handler service is invalid.');
                    }

                    if (!$membershipNotFound instanceof DomainExceptionHandlerInterface) {
                        throw new LogicException('Membership not found exception handler service is invalid.');
                    }

                    return [
                        $installSessionNotFound,
                        $installSessionConflict,
                        $installSessionNotReady,
                        $invalidCredentials,
                        $loginRateLimited,
                        $unauthorized,
                        $operatorEmailConflict,
                        $operatorValidation,
                        $forbidden,
                        $organizationValidation,
                        $organizationSlugConflict,
                        $organizationNotFound,
                        $membershipValidation,
                        $membershipConflict,
                        $membershipInvariant,
                        $membershipNotFound,
                    ];
                },
            );
    }
}
