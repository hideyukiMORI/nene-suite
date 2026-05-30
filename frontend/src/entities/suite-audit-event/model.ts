export interface SuiteAuditEvent {
  id: string
  action: string
  entityType: string
  entityId: string
  actorLabel: string | null
  source: string
  createdAt: string
  installSessionId: string | null
}

export interface SuiteAuditEventPage {
  items: SuiteAuditEvent[]
  nextCursor: string | null
}
