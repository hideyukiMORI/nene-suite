<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Generates the NENE_SUITE_* env file from a completed install session and records
 * `env_config.written` in the suite audit trail (audit-trail §4).
 * A fresh JWT secret is generated on every call (bin2hex random_bytes).
 * The written file contains the plaintext secret; the audit event has it redacted.
 */
final readonly class WriteEnvConfigUseCase implements WriteEnvConfigUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private SuiteEnvConfigFactory $factory,
        private SuiteEnvWriterInterface $writer,
        private SuiteAuditRecorderInterface $audit,
        private SuiteAppUrlReaderInterface $urls,
        private string $suiteId,
        private string $baseUrl,
    ) {
    }

    public function execute(WriteEnvConfigInput $input): WriteEnvConfigOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        $appUrls = [];
        foreach ($session->selectedApps as $catalogId) {
            $url = $this->urls->publicUrl($catalogId);
            if ($url !== null) {
                $appUrls[$catalogId] = $url;
            }
        }

        $config = $this->factory->create(
            suiteId: $this->suiteId,
            orgExternalId: $session->orgExternalId ?? $this->suiteId,
            orgName: $session->orgDisplayName,
            baseUrl: $this->baseUrl,
            jwtSecret: bin2hex(random_bytes(32)),
            installedApps: $session->selectedApps,
            appUrls: $appUrls,
        );

        $this->writer->write($config);

        $this->audit->record(new RecordSuiteAuditEventCommand(
            suiteId: $this->suiteId,
            action: 'env_config.written',
            entityType: 'suite_env_config',
            entityId: $this->suiteId,
            beforeJson: null,
            afterJson: $config->toSanitizedMap(),
            actorUserId: $input->actorUserId,
            source: 'installer_ui',
            installSessionId: $input->installSessionId,
            orgExternalId: $session->orgExternalId,
            requestId: $input->requestId,
        ));

        return new WriteEnvConfigOutput($config->toSanitizedMap());
    }
}
