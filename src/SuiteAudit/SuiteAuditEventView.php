<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

/**
 * camelCase HTTP representation (docs/openapi/openapi.yaml SuiteAuditEvent):
 * `before_json`→`before`, `after_json`→`after`, `metadata_json`→`metadata`,
 * `*_id`→`*Id`.
 */
final readonly class SuiteAuditEventView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(SuiteAuditEvent $event): array
    {
        return [
            'id' => $event->id,
            'suiteId' => $event->suiteId,
            'orgExternalId' => $event->orgExternalId,
            'actorUserId' => $event->actorUserId,
            'actorLabel' => $event->actorLabel,
            'action' => $event->action,
            'entityType' => $event->entityType,
            'entityId' => $event->entityId,
            'before' => $event->before,
            'after' => $event->after,
            'createdAt' => $event->createdAt,
            'source' => $event->source,
            'requestId' => $event->requestId,
            'installSessionId' => $event->installSessionId,
            'metadata' => $event->metadata,
        ];
    }
}
