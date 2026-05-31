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
 *  5. WriteEnvConfig — write NENE_SUITE_* env file
 *  6. CreateOperator — initial apex operator
 *  7. CompleteInstallSession — write install manifest
 */
final readonly class InstallerUseCase implements InstallerUseCaseInterface
{
    public function __construct(
        private StartInstallSessionUseCaseInterface $startSession,
        private UpdateAppSelectionUseCaseInterface $updateSelection,
        private AcceptDisclaimerUseCaseInterface $acceptDisclaimer,
        private ProvisionAppDatabasesUseCaseInterface $provisionDatabases,
        private WriteEnvConfigUseCaseInterface $writeEnvConfig,
        private CreateOperatorUseCaseInterface $createOperator,
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

        $envOutput = $this->writeEnvConfig->execute(new WriteEnvConfigInput(
            installSessionId: $sessionId,
        ));

        $operatorOutput = $this->createOperator->execute(new CreateOperatorInput(
            email: $input->operatorEmail,
            password: $input->operatorPassword,
            displayName: $input->operatorDisplayName,
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
