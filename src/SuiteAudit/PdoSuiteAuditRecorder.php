<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Appends sanitized audit rows to the suite control database's
 * suite_audit_events table (append-only — never updates or deletes).
 */
final readonly class PdoSuiteAuditRecorder implements SuiteAuditRecorderInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SuiteAuditSanitizer $sanitizer,
    ) {
    }

    public function record(RecordSuiteAuditEventCommand $command): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO suite_audit_events (
                    id, suite_id, org_external_id, actor_user_id, actor_label,
                    action, entity_type, entity_id, before_json, after_json,
                    created_at, source, request_id, install_session_id, metadata_json
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                (string) new Ulid(),
                $command->suiteId,
                $command->orgExternalId,
                $command->actorUserId,
                $command->actorLabel,
                $command->action,
                $command->entityType,
                $command->entityId,
                $this->encode($command->beforeJson),
                $this->encode($command->afterJson),
                gmdate('Y-m-d\TH:i:s\Z'),
                $command->source,
                $command->requestId,
                $command->installSessionId,
                $this->encode($command->metadataJson),
            ],
        );
    }

    /**
     * @param array<array-key, mixed>|null $data
     */
    private function encode(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        return json_encode(
            $this->sanitizer->sanitize($data),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
