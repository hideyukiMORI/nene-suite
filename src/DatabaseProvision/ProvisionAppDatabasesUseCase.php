<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Provisions one MySQL database per selected app and records `database.provisioned`
 * for each in the suite audit trail (audit-trail §4).
 * No passwords or connection DSNs appear in `after_json` — only catalog id and
 * database name.
 */
final readonly class ProvisionAppDatabasesUseCase implements ProvisionAppDatabasesUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private AppDatabaseNamer $namer,
        private DatabaseProvisionerInterface $provisioner,
        private SuiteAuditRecorderInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(ProvisionAppDatabasesInput $input): ProvisionAppDatabasesOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        $provisioned = [];
        foreach ($session->selectedApps as $catalogId) {
            $databaseName = $this->namer->databaseName($catalogId);

            $this->provisioner->provision($databaseName);

            $this->audit->record(new RecordSuiteAuditEventCommand(
                suiteId: $this->suiteId,
                action: 'database.provisioned',
                entityType: 'app_database',
                entityId: $catalogId,
                beforeJson: null,
                afterJson: [
                    'catalog_id'    => $catalogId,
                    'database_name' => $databaseName,
                ],
                actorUserId: $input->actorUserId,
                source: 'installer_ui',
                installSessionId: $input->installSessionId,
                orgExternalId: $session->orgExternalId,
                requestId: $input->requestId,
            ));

            $provisioned[] = new ProvisionedAppDatabase($catalogId, $databaseName);
        }

        return new ProvisionAppDatabasesOutput($provisioned);
    }
}
