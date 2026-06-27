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
  it('emits the audit-evidence header with before/after/metadata columns', () => {
    const csv = buildAuditCsv([event({})])
    expect(csv.split('\n')[0]).toBe(
      'time,change,action,entity_type,entity_id,actor,source,before,after,metadata,request_id,org_external_id,suite_id',
    )
  })

  it('carries the before/after snapshots as JSON', () => {
    const csv = buildAuditCsv([
      event({
        action: 'membership.role_changed',
        before: { role: 'member' },
        after: { role: 'admin' },
      }),
    ])
    expect(csv).toContain('"{""role"":""member""}"')
    expect(csv).toContain('"{""role"":""admin""}"')
  })

  it('derives the change kind and falls back to source for a null actor', () => {
    const csv = buildAuditCsv([event({ actorLabel: null, before: null, after: { x: 1 } })])
    const row = csv.split('\n')[1] ?? ''
    expect(row).toContain(',create,')
    expect(row).toContain(',apex_ui,')
  })

  it('escapes commas and quotes in values', () => {
    const csv = buildAuditCsv([event({ action: 'a,b' })])
    expect(csv).toContain('"a,b"')
  })
})
