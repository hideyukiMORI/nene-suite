import { describe, expect, it } from 'vitest'
import type { SuiteAuditEvent } from '@/entities/suite-audit-event'
import { buildAuditCsv } from './csv'

function event(overrides: Partial<SuiteAuditEvent>): SuiteAuditEvent {
  return {
    id: '1',
    suiteId: 'suite-1',
    action: 'organization.created',
    entityType: 'organization',
    entityId: 'org-1',
    actorUserId: null,
    actorLabel: 'op@example.com',
    source: 'apex_ui',
    orgExternalId: null,
    requestId: null,
    installSessionId: null,
    createdAt: '2026-01-01T00:00:00Z',
    before: null,
    after: null,
    metadata: null,
    ...overrides,
  }
}

describe('buildAuditCsv', () => {
  it('builds a header row and one row per event', () => {
    const csv = buildAuditCsv([event({})])
    const lines = csv.split('\n')
    expect(lines[0]).toBe('time,actor,action,entity_type,entity_id')
    expect(lines[1]).toBe(
      '2026-01-01T00:00:00Z,op@example.com,organization.created,organization,org-1',
    )
  })

  it('falls back to source when actorLabel is null and escapes commas', () => {
    const csv = buildAuditCsv([event({ actorLabel: null, action: 'a,b' })])
    expect(csv).toContain(',apex_ui,')
    expect(csv).toContain('"a,b"')
  })
})
