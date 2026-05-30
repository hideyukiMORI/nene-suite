// Wire DTOs for the orchestration audit trail (docs/openapi/openapi.yaml SuiteAuditEvent).

export interface SuiteAuditEventDto {
  id: string
  suiteId: string
  orgExternalId: string | null
  actorUserId: string | null
  actorLabel: string | null
  action: string
  entityType: string
  entityId: string
  before: Record<string, unknown> | null
  after: Record<string, unknown> | null
  createdAt: string
  source: string
  requestId: string | null
  installSessionId: string | null
  metadata: Record<string, unknown> | null
}

export interface SuiteAuditEventListDto {
  items: SuiteAuditEventDto[]
  nextCursor: string | null
}
