import type { SuiteAuditEventDto, SuiteAuditEventListDto } from './api-types'
import type { SuiteAuditEvent, SuiteAuditEventPage } from './model'

export function toSuiteAuditEvent(dto: SuiteAuditEventDto): SuiteAuditEvent {
  return {
    id: dto.id,
    suiteId: dto.suiteId,
    action: dto.action,
    entityType: dto.entityType,
    entityId: dto.entityId,
    actorUserId: dto.actorUserId ?? null,
    actorLabel: dto.actorLabel ?? null,
    source: dto.source,
    orgExternalId: dto.orgExternalId ?? null,
    requestId: dto.requestId ?? null,
    installSessionId: dto.installSessionId ?? null,
    createdAt: dto.createdAt,
    before: dto.before ?? null,
    after: dto.after ?? null,
    metadata: dto.metadata ?? null,
  }
}

export function toSuiteAuditEventPage(dto: SuiteAuditEventListDto): SuiteAuditEventPage {
  return {
    items: dto.items.map(toSuiteAuditEvent),
    nextCursor: dto.nextCursor,
  }
}
