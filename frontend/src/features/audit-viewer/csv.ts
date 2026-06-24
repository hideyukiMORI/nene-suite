import type { SuiteAuditEvent } from '@/entities/suite-audit-event'

function escapeCell(value: string): string {
  return /[",\n]/.test(value) ? `"${value.replace(/"/g, '""')}"` : value
}

/** Builds a CSV (header + rows) from the currently-loaded/filtered audit events. */
export function buildAuditCsv(events: SuiteAuditEvent[]): string {
  const header = ['time', 'actor', 'action', 'entity_type', 'entity_id']
  const rows = events.map((event) => [
    event.createdAt,
    event.actorLabel ?? event.source,
    event.action,
    event.entityType,
    event.entityId,
  ])
  return [header, ...rows].map((cols) => cols.map(escapeCell).join(',')).join('\n')
}
