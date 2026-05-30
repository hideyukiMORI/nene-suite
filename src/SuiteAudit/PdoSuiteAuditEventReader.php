<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Reads the append-only suite_audit_events table newest-first. Cursor pagination
 * uses the ULID primary key (`id`), which is time-ordered, so `id DESC` matches
 * `created_at DESC` while staying unique and stable.
 */
final readonly class PdoSuiteAuditEventReader implements SuiteAuditEventReaderInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function list(SuiteAuditEventQuery $query): SuiteAuditEventPage
    {
        $limit = max(1, min($query->limit, SuiteAuditEventQuery::MAX_LIMIT));

        $conditions = [];
        $params = [];

        if ($query->cursor !== null && $query->cursor !== '') {
            $conditions[] = 'id < ?';
            $params[] = $query->cursor;
        }

        if ($query->installSessionId !== null) {
            $conditions[] = 'install_session_id = ?';
            $params[] = $query->installSessionId;
        }

        if ($query->action !== null) {
            $conditions[] = 'action = ?';
            $params[] = $query->action;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $params[] = $limit + 1;

        $rows = $this->query->fetchAll(
            "SELECT * FROM suite_audit_events{$where} ORDER BY id DESC LIMIT ?",
            $params,
        );

        $hasMore = count($rows) > $limit;
        $items = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            $items[] = $this->hydrate($row);
        }

        $nextCursor = $hasMore && $items !== [] ? $items[count($items) - 1]->id : null;

        return new SuiteAuditEventPage($items, $nextCursor);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): SuiteAuditEvent
    {
        return new SuiteAuditEvent(
            id: (string) $row['id'],
            suiteId: (string) $row['suite_id'],
            orgExternalId: $this->nullableString($row['org_external_id'] ?? null),
            actorUserId: $this->nullableString($row['actor_user_id'] ?? null),
            actorLabel: $this->nullableString($row['actor_label'] ?? null),
            action: (string) $row['action'],
            entityType: (string) $row['entity_type'],
            entityId: (string) $row['entity_id'],
            before: $this->decodeJson($row['before_json'] ?? null),
            after: $this->decodeJson($row['after_json'] ?? null),
            createdAt: (string) $row['created_at'],
            source: (string) $row['source'],
            requestId: $this->nullableString($row['request_id'] ?? null),
            installSessionId: $this->nullableString($row['install_session_id'] ?? null),
            metadata: $this->decodeJson($row['metadata_json'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
