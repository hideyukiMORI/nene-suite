import type { AuditSnapshot, SuiteAuditEvent } from '@/entities/suite-audit-event'
import { classifyChange } from './lib/audit-format'

function escapeCell(value: string): string {
  return /[",\n]/.test(value) ? `"${value.replace(/"/g, '""')}"` : value
}

/** Serialize a sanitized snapshot for the CSV (already `[REDACTED]`-safe); null → empty cell. */
function jsonCell(value: AuditSnapshot | null): string {
  return value === null ? '' : JSON.stringify(value)
}

const HEADER = [
  'time',
  'change',
  'action',
  'entity_type',
  'entity_id',
  'actor',
  'source',
  'before',
  'after',
  'metadata',
  'request_id',
  'org_external_id',
  'suite_id',
]

/**
 * Builds an audit-evidence CSV (header + one row per event) from the
 * currently-loaded/filtered events. Carries the full **before / after / metadata**
 * snapshots so the export is usable as a primary record — not just a summary.
 */
export function buildAuditCsv(events: SuiteAuditEvent[]): string {
  const rows = events.map((event) => [
    event.createdAt,
    classifyChange(event),
    event.action,
    event.entityType,
    event.entityId,
    event.actorLabel ?? event.source,
    event.source,
    jsonCell(event.before),
    jsonCell(event.after),
    jsonCell(event.metadata),
    event.requestId ?? '',
    event.orgExternalId ?? '',
    event.suiteId,
  ])
  return [HEADER, ...rows].map((cols) => cols.map(escapeCell).join(',')).join('\n')
}
