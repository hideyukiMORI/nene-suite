/** Sanitized before/after/metadata snapshot — free-form JSON object (secrets already `[REDACTED]`). */
export type AuditSnapshot = Record<string, unknown>

export interface SuiteAuditEvent {
  id: string
  suiteId: string
  action: string
  entityType: string
  entityId: string
  actorUserId: string | null
  actorLabel: string | null
  source: string
  orgExternalId: string | null
  requestId: string | null
  installSessionId: string | null
  createdAt: string
  before: AuditSnapshot | null
  after: AuditSnapshot | null
  metadata: AuditSnapshot | null
}

export interface SuiteAuditEventPage {
  items: SuiteAuditEvent[]
  nextCursor: string | null
}
