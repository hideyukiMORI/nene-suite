<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeSuite\AppSelection\UpdateAppSelectionUseCaseInterface;
use NeNeSuite\Auth\CreateOperatorUseCaseInterface;
use NeNeSuite\Auth\OperatorRepositoryInterface;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesUseCaseInterface;
use NeNeSuite\Http\RuntimeServiceProvider;
use NeNeSuite\InstallManifest\InstallManifestRepositoryInterface;
use NeNeSuite\InstallSession\AcceptDisclaimerUseCaseInterface;
use NeNeSuite\InstallSession\CompleteInstallSessionUseCaseInterface;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\InstallSession\StartInstallSessionUseCaseInterface;
use NeNeSuite\SuiteEnv\WriteEnvConfigUseCaseInterface;
use NeNeSuite\Tenancy\CreateOrganizationUseCaseInterface;
use NeNeSuite\Tenancy\GrantMembershipUseCaseInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class InstallerServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                InstallerEnvReader::class,
                static fn (ContainerInterface $container): InstallerEnvReader => new InstallerEnvReader(),
            )
            ->set(
                BootstrapDefaultOrganizationUseCaseInterface::class,
                static function (ContainerInterface $container): BootstrapDefaultOrganizationUseCaseInterface {
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $createOrganization = $container->get(CreateOrganizationUseCaseInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $grantMembership = $container->get(GrantMembershipUseCaseInterface::class);
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$createOrganization instanceof CreateOrganizationUseCaseInterface) {
                        throw new LogicException('CreateOrganization use case service is invalid.');
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

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    return new BootstrapDefaultOrganizationUseCase(
                        $sessions,
                        $createOrganization,
                        $organizations,
                        $grantMembership,
                        $memberships,
                        $suiteId,
                    );
                },
            )
            ->set(
                BackfillTenancyUseCaseInterface::class,
                static function (ContainerInterface $container): BackfillTenancyUseCaseInterface {
                    $operators = $container->get(OperatorRepositoryInterface::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $memberships = $container->get(MembershipRepositoryInterface::class);
                    $createOrganization = $container->get(CreateOrganizationUseCaseInterface::class);
                    $grantMembership = $container->get(GrantMembershipUseCaseInterface::class);
                    $sessions = $container->get(InstallSessionRepositoryInterface::class);
                    $manifests = $container->get(InstallManifestRepositoryInterface::class);
                    $suiteId = $container->get(RuntimeServiceProvider::SUITE_ID);
                    $orgExternalId = $container->get(RuntimeServiceProvider::SUITE_ORG_EXTERNAL_ID);

                    if (!$operators instanceof OperatorRepositoryInterface) {
                        throw new LogicException('Operator repository service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$memberships instanceof MembershipRepositoryInterface) {
                        throw new LogicException('Membership repository service is invalid.');
                    }

                    if (!$createOrganization instanceof CreateOrganizationUseCaseInterface) {
                        throw new LogicException('CreateOrganization use case service is invalid.');
                    }

                    if (!$grantMembership instanceof GrantMembershipUseCaseInterface) {
                        throw new LogicException('GrantMembership use case service is invalid.');
                    }

                    if (!$sessions instanceof InstallSessionRepositoryInterface) {
                        throw new LogicException('Install session repository service is invalid.');
                    }

                    if (!$manifests instanceof InstallManifestRepositoryInterface) {
                        throw new LogicException('Install manifest repository service is invalid.');
                    }

                    if (!is_string($suiteId) || $suiteId === '') {
                        throw new LogicException('Suite id service is invalid.');
                    }

                    if (!is_string($orgExternalId) || $orgExternalId === '') {
                        throw new LogicException('Suite org external id service is invalid.');
                    }

                    return new BackfillTenancyUseCase(
                        $operators,
                        $organizations,
                        $memberships,
                        $createOrganization,
                        $grantMembership,
                        $sessions,
                        $manifests,
                        $suiteId,
                        $orgExternalId,
                    );
                },
            )
            ->set(
                InstallerUseCaseInterface::class,
                static function (ContainerInterface $container): InstallerUseCaseInterface {
                    $startSession = $container->get(StartInstallSessionUseCaseInterface::class);
                    $updateSelection = $container->get(UpdateAppSelectionUseCaseInterface::class);
                    $acceptDisclaimer = $container->get(AcceptDisclaimerUseCaseInterface::class);
                    $provisionDatabases = $container->get(ProvisionAppDatabasesUseCaseInterface::class);
                    $createOperator = $container->get(CreateOperatorUseCaseInterface::class);
                    $bootstrapOrganization = $container->get(BootstrapDefaultOrganizationUseCaseInterface::class);
                    $writeEnvConfig = $container->get(WriteEnvConfigUseCaseInterface::class);
                    $completeSession = $container->get(CompleteInstallSessionUseCaseInterface::class);

                    if (!$startSession instanceof StartInstallSessionUseCaseInterface) {
                        throw new LogicException('StartInstallSession use case service is invalid.');
                    }

                    if (!$updateSelection instanceof UpdateAppSelectionUseCaseInterface) {
                        throw new LogicException('UpdateAppSelection use case service is invalid.');
                    }

                    if (!$acceptDisclaimer instanceof AcceptDisclaimerUseCaseInterface) {
                        throw new LogicException('AcceptDisclaimer use case service is invalid.');
                    }

                    if (!$provisionDatabases instanceof ProvisionAppDatabasesUseCaseInterface) {
                        throw new LogicException('ProvisionAppDatabases use case service is invalid.');
                    }

                    if (!$createOperator instanceof CreateOperatorUseCaseInterface) {
                        throw new LogicException('CreateOperator use case service is invalid.');
                    }

                    if (!$bootstrapOrganization instanceof BootstrapDefaultOrganizationUseCaseInterface) {
                        throw new LogicException('BootstrapDefaultOrganization use case service is invalid.');
                    }

                    if (!$writeEnvConfig instanceof WriteEnvConfigUseCaseInterface) {
                        throw new LogicException('WriteEnvConfig use case service is invalid.');
                    }

                    if (!$completeSession instanceof CompleteInstallSessionUseCaseInterface) {
                        throw new LogicException('CompleteInstallSession use case service is invalid.');
                    }

                    return new InstallerUseCase(
                        $startSession,
                        $updateSelection,
                        $acceptDisclaimer,
                        $provisionDatabases,
                        $createOperator,
                        $bootstrapOrganization,
                        $writeEnvConfig,
                        $completeSession,
                    );
                },
            );
    }
}
