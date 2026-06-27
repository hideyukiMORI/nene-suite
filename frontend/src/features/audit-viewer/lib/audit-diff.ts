import type { AuditSnapshot } from '@/entities/suite-audit-event'

export type DiffStatus = 'added' | 'removed' | 'changed' | 'same'

/** A formatted value on one side of a diff; `present:false` means the key was absent there. */
export interface DiffValue {
  readonly present: boolean
  readonly text: string
  readonly redacted: boolean
}

export interface DiffContainerRow {
  readonly kind: 'container'
  readonly key: string
  readonly depth: number
  readonly isArray: boolean
  readonly status: DiffStatus
}

export interface DiffLeafRow {
  readonly kind: 'leaf'
  readonly key: string
  readonly depth: number
  readonly status: DiffStatus
  readonly before: DiffValue
  readonly after: DiffValue
}

export type DiffRow = DiffContainerRow | DiffLeafRow

export interface DiffResult {
  readonly rows: readonly DiffRow[]
  readonly added: number
  readonly removed: number
  readonly changed: number
  readonly same: number
}

const MISSING = Symbol('missing')
type Cell = unknown

function isObject(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object'
}

function formatValue(value: Cell): DiffValue {
  if (value === MISSING) return { present: false, text: '', redacted: false }
  if (value === null) return { present: true, text: 'null', redacted: false }
  if (value === '[REDACTED]') return { present: true, text: '[REDACTED]', redacted: true }
  if (typeof value === 'string') return { present: true, text: `"${value}"`, redacted: false }
  if (typeof value === 'number' || typeof value === 'boolean') {
    return { present: true, text: String(value), redacted: false }
  }
  return { present: true, text: JSON.stringify(value), redacted: false }
}

interface RawRow {
  kind: 'container' | 'leaf'
  key: string
  depth: number
  isArray: boolean
  status: DiffStatus
  before: Cell
  after: Cell
}

/**
 * Recursively diffs two sanitized audit snapshots into a flat, depth-tagged row
 * matrix (mirrors the ClaudeDesign reference). Containers (`{}` / `[]`) get their own
 * row; leaves carry per-side values + an added/removed/changed/same status. A null
 * side (create / delete) makes every key added / removed.
 */
export function buildDiff(before: AuditSnapshot | null, after: AuditSnapshot | null): DiffResult {
  const raw: RawRow[] = []

  const walk = (b: Cell, a: Cell, depth: number): void => {
    const bObj = isObject(b) ? b : undefined
    const aObj = isObject(a) ? a : undefined
    const keys: string[] = []
    if (bObj !== undefined) for (const key of Object.keys(bObj)) keys.push(key)
    if (aObj !== undefined)
      for (const key of Object.keys(aObj)) if (!keys.includes(key)) keys.push(key)
    for (const key of keys) {
      const inB = bObj !== undefined && Object.prototype.hasOwnProperty.call(bObj, key)
      const inA = aObj !== undefined && Object.prototype.hasOwnProperty.call(aObj, key)
      node(key, inB ? bObj[key] : MISSING, inA ? aObj[key] : MISSING, depth)
    }
  }

  const node = (key: string, bv: Cell, av: Cell, depth: number): void => {
    const bContainer = isObject(bv)
    const aContainer = isObject(av)
    if (bContainer && aContainer && Array.isArray(bv) === Array.isArray(av)) {
      const container: RawRow = {
        kind: 'container',
        key,
        depth,
        isArray: Array.isArray(bv),
        status: 'same',
        before: bv,
        after: av,
      }
      raw.push(container)
      const childStart = raw.length
      walk(bv, av, depth + 1)
      container.status = raw.slice(childStart).some((row) => row.status !== 'same')
        ? 'changed'
        : 'same'
      return
    }
    if (aContainer && bv === MISSING) {
      raw.push({
        kind: 'container',
        key,
        depth,
        isArray: Array.isArray(av),
        status: 'added',
        before: MISSING,
        after: av,
      })
      walk(Array.isArray(av) ? [] : {}, av, depth + 1)
      return
    }
    if (bContainer && av === MISSING) {
      raw.push({
        kind: 'container',
        key,
        depth,
        isArray: Array.isArray(bv),
        status: 'removed',
        before: bv,
        after: MISSING,
      })
      walk(bv, Array.isArray(bv) ? [] : {}, depth + 1)
      return
    }
    let status: DiffStatus
    if (bv === MISSING) status = 'added'
    else if (av === MISSING) status = 'removed'
    else if (JSON.stringify(bv) !== JSON.stringify(av)) status = 'changed'
    else status = 'same'
    raw.push({ kind: 'leaf', key, depth, isArray: false, status, before: bv, after: av })
  }

  walk(before ?? {}, after ?? {}, 0)

  let added = 0
  let removed = 0
  let changed = 0
  let same = 0
  for (const row of raw) {
    if (row.kind !== 'leaf') continue
    if (row.status === 'added') added += 1
    else if (row.status === 'removed') removed += 1
    else if (row.status === 'changed') changed += 1
    else same += 1
  }

  const rows: DiffRow[] = raw.map((row) =>
    row.kind === 'container'
      ? {
          kind: 'container',
          key: row.key,
          depth: row.depth,
          isArray: row.isArray,
          status: row.status,
        }
      : {
          kind: 'leaf',
          key: row.key,
          depth: row.depth,
          status: row.status,
          before: formatValue(row.before),
          after: formatValue(row.after),
        },
  )

  return { rows, added, removed, changed, same }
}
