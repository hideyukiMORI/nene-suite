import type { SuiteAuditEventDto, SuiteAuditEventListDto } from './api-types'
import type { SuiteAuditEvent, SuiteAuditEventPage } from './model'

export function toSuiteAuditEvent(dto: SuiteAuditEventDto): SuiteAuditEvent {
  return {
    id: dto.id,
    action: dto.action,
    entityType: dto.entityType,
    entityId: dto.entityId,
    actorLabel: dto.actorLabel ?? null,
    source: dto.source,
    createdAt: dto.createdAt,
    installSessionId: dto.installSessionId ?? null,
  }
}

export function toSuiteAuditEventPage(dto: SuiteAuditEventListDto): SuiteAuditEventPage {
  return {
    items: dto.items.map(toSuiteAuditEvent),
    nextCursor: dto.nextCursor,
  }
}
