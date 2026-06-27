import { describe, expect, it } from 'vitest'
import { buildDiff } from './audit-diff'

describe('buildDiff', () => {
  it('marks every leaf added when before is null (create)', () => {
    const result = buildDiff(null, { name: 'Acme', role: 'admin' })
    expect(result.added).toBe(2)
    expect(result.removed).toBe(0)
    expect(result.changed).toBe(0)
    expect(result.rows.every((row) => row.status === 'added')).toBe(true)
  })

  it('marks every leaf removed when after is null (delete)', () => {
    const result = buildDiff({ role: 'admin' }, null)
    expect(result.removed).toBe(1)
    expect(result.added).toBe(0)
  })

  it('distinguishes changed from same leaves', () => {
    const result = buildDiff({ name: 'Acme KK', slug: 'acme' }, { name: 'Acme Corp', slug: 'acme' })
    expect(result.changed).toBe(1)
    expect(result.same).toBe(1)
    const nameRow = result.rows.find((row) => row.kind === 'leaf' && row.key === 'name')
    expect(nameRow?.status).toBe('changed')
  })

  it('walks nested objects/arrays with depth + container rows', () => {
    const result = buildDiff({ targets: [{ mode: 'provision' }] }, { targets: [{ mode: 'adopt' }] })
    const container = result.rows.find((row) => row.kind === 'container' && row.key === 'targets')
    expect(container?.status).toBe('changed')
    const leaf = result.rows.find((row) => row.kind === 'leaf' && row.key === 'mode')
    expect(leaf?.depth).toBeGreaterThan(0)
    expect(result.changed).toBe(1)
  })

  it('flags [REDACTED] values', () => {
    const result = buildDiff(null, { NENE_SUITE_JWT_SECRET: '[REDACTED]' })
    const leaf = result.rows.find((row) => row.kind === 'leaf')
    expect(leaf?.kind).toBe('leaf')
    if (leaf?.kind === 'leaf') expect(leaf.after.redacted).toBe(true)
  })

  it('reports same for an unchanged snapshot', () => {
    const result = buildDiff({ a: 1, nested: { b: 2 } }, { a: 1, nested: { b: 2 } })
    expect(result.added + result.removed + result.changed).toBe(0)
    expect(result.same).toBe(2)
  })
})
