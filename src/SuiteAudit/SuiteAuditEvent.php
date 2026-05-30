<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

/**
 * Read model for one persisted audit row. `before` / `after` / `metadata` are the
 * already-sanitized snapshots decoded from the JSON columns (no secrets — they
 * were redacted at write time, audit-trail.md §5).
 */
final readonly class SuiteAuditEvent
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $id,
        public string $suiteId,
        public ?string $orgExternalId,
        public ?string $actorUserId,
        public ?string $actorLabel,
        public string $action,
        public string $entityType,
        public string $entityId,
        public ?array $before,
        public ?array $after,
        public string $createdAt,
        public string $source,
        public ?string $requestId,
        public ?string $installSessionId,
        public ?array $metadata,
    ) {
    }
}
