<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

/**
 * Immutable instruction to append one orchestration audit row. Field names and
 * the `action` / `entity_type` / `source` vocabularies are bound by
 * docs/explanation/audit-trail.md §3–§4.
 */
final readonly class RecordSuiteAuditEventCommand
{
    /**
     * @param array<string, mixed>|null $beforeJson sanitized snapshot before mutation; null on create
     * @param array<string, mixed>|null $afterJson  sanitized snapshot after mutation
     * @param array<string, mixed>|null $metadataJson event-specific extras
     */
    public function __construct(
        public string $suiteId,
        public string $action,
        public string $entityType,
        public string $entityId,
        public ?array $beforeJson = null,
        public ?array $afterJson = null,
        public ?string $actorUserId = null,
        public ?string $actorLabel = null,
        public string $source = 'installer_ui',
        public ?string $installSessionId = null,
        public ?string $orgExternalId = null,
        public ?string $requestId = null,
        public ?array $metadataJson = null,
    ) {
    }
}
