<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use NeNeSuite\AppSelection\UpdateAppSelectionInput;
use NeNeSuite\AppSelection\UpdateAppSelectionUseCaseInterface;
use NeNeSuite\Auth\CreateOperatorInput;
use NeNeSuite\Auth\CreateOperatorUseCaseInterface;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesInput;
use NeNeSuite\DatabaseProvision\ProvisionAppDatabasesUseCaseInterface;
use NeNeSuite\InstallSession\AcceptDisclaimerInput;
use NeNeSuite\InstallSession\AcceptDisclaimerUseCaseInterface;
use NeNeSuite\InstallSession\CompleteInstallSessionInput;
use NeNeSuite\InstallSession\CompleteInstallSessionUseCaseInterface;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\InstallSession\StartInstallSessionInput;
use NeNeSuite\InstallSession\StartInstallSessionUseCaseInterface;
use NeNeSuite\SuiteEnv\WriteEnvConfigInput;
use NeNeSuite\SuiteEnv\WriteEnvConfigUseCaseInterface;

/**
 * Orchestrates a full Tier B suite installation in a single transaction-like
 * sequence. Called by `installer/install.php`; not exposed over HTTP.
 *
 * Steps (in order):
 *  1. StartInstallSession
 *  2. UpdateAppSelection (dependency-resolved)
 *  3. AcceptDisclaimer
 *  4. ProvisionAppDatabases — CREATE DATABASE per selected app
 *  5. CreateOperator — initial apex operator (before env write; needed for the membership grant)
 *  6. BootstrapDefaultOrganization — default org + operator superadmin/admin; stamps session orgExternalId
 *  7. WriteEnvConfig — write NENE_SUITE_* env file (now carries the org external_id)
 *  8. CompleteInstallSession — write install manifest
 */
final readonly class InstallerUseCase implements InstallerUseCaseInterface
{
    public function __construct(
        private StartInstallSessionUseCaseInterface $startSession,
        private UpdateAppSelectionUseCaseInterface $updateSelection,
        private AcceptDisclaimerUseCaseInterface $acceptDisclaimer,
        private ProvisionAppDatabasesUseCaseInterface $provisionDatabases,
        private CreateOperatorUseCaseInterface $createOperator,
        private BootstrapDefaultOrganizationUseCaseInterface $bootstrapOrganization,
        private WriteEnvConfigUseCaseInterface $writeEnvConfig,
        private CompleteInstallSessionUseCaseInterface $completeSession,
    ) {
    }

    public function execute(InstallerInput $input): InstallerOutput
    {
        $startOutput = $this->startSession->execute(new StartInstallSessionInput(
            tier: InstallTier::B,
            selectedApps: $input->selectedApps,
            orgDisplayName: $input->orgName,
        ));
        $sessionId = $startOutput->session->id;

        $this->updateSelection->execute(new UpdateAppSelectionInput(
            installSessionId: $sessionId,
            selectedApps: $input->selectedApps,
        ));

        $this->acceptDisclaimer->execute(new AcceptDisclaimerInput(
            installSessionId: $sessionId,
            disclaimerVersion: $input->disclaimerVersion,
            acceptedLabel: $input->operatorEmail,
        ));

        $provisionOutput = $this->provisionDatabases->execute(new ProvisionAppDatabasesInput(
            installSessionId: $sessionId,
        ));

        $operatorOutput = $this->createOperator->execute(new CreateOperatorInput(
            email: $input->operatorEmail,
            password: $input->operatorPassword,
            displayName: $input->operatorDisplayName,
        ));

        // Create the default org + operator memberships and stamp the session's org
        // external_id BEFORE WriteEnvConfig, so the env/manifest carry the real value.
        $this->bootstrapOrganization->execute(new BootstrapDefaultOrganizationInput(
            installSessionId: $sessionId,
            orgName: $input->orgName,
            operatorId: $operatorOutput->operator->id,
        ));

        $envOutput = $this->writeEnvConfig->execute(new WriteEnvConfigInput(
            installSessionId: $sessionId,
        ));

        $completeOutput = $this->completeSession->execute(new CompleteInstallSessionInput(
            installSessionId: $sessionId,
        ));

        return new InstallerOutput(
            installSessionId: $sessionId,
            installManifestId: (string) $completeOutput->session->installManifestId,
            operatorId: $operatorOutput->operator->id,
            provisionedDatabases: array_map(
                static fn ($db) => $db->databaseName,
                $provisionOutput->provisioned,
            ),
            envFilePath: $envOutput->sanitizedEnvMap['NENE_SUITE_BASE_URL'] !== ''
                ? (string) ($_SERVER['NENE_SUITE_ENV_FILE_PATH'] ?? '.env.suite')
                : '.env.suite',
            completedAt: $completeOutput->session->completedAt ?? gmdate('Y-m-d\TH:i:s\Z'),
        );
    }
}
