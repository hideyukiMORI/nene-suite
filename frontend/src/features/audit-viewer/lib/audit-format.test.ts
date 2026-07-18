import { describe, expect, it } from 'vitest'
import {
  absoluteTime,
  actionGloss,
  CHANGE_ICON,
  CHANGE_SIGN,
  classifyChange,
  entityIcon,
  relativeTime,
  shortId,
  sourceIcon,
} from './audit-format'

describe('classifyChange', () => {
  it('is a create when before is null and after is present', () => {
    expect(classifyChange({ before: null, after: {} })).toBe('create')
  })

  it('is a delete when before is present and after is null', () => {
    expect(classifyChange({ before: {}, after: null })).toBe('delete')
  })

  it('is an update when both snapshots are present', () => {
    expect(classifyChange({ before: { name: 'a' }, after: { name: 'b' } })).toBe('update')
  })

  it('falls back to update when both are null (no snapshot recorded)', () => {
    expect(classifyChange({ before: null, after: null })).toBe('update')
  })
})

describe('CHANGE_ICON / CHANGE_SIGN', () => {
  it('has an icon and a sign for every change kind', () => {
    for (const kind of ['create', 'update', 'delete'] as const) {
      expect(CHANGE_ICON[kind]).toBeTruthy()
      expect(CHANGE_SIGN[kind]).toBeTruthy()
    }
    // Signs are distinct so they never collapse into one glyph.
    expect(new Set(Object.values(CHANGE_SIGN)).size).toBe(3)
  })
})

describe('entityIcon', () => {
  it('maps a known entity type to its Material Symbol', () => {
    expect(entityIcon('organization')).toBe('corporate_fare')
    expect(entityIcon('federation_signing_key')).toBe('key')
  })

  it('falls back to history for an unknown entity type', () => {
    expect(entityIcon('something_new')).toBe('history')
  })
})

describe('sourceIcon', () => {
  it('maps a known source to its Material Symbol', () => {
    expect(sourceIcon('installer_cli')).toBe('terminal')
    expect(sourceIcon('system')).toBe('smart_toy')
  })

  it('falls back to bolt for an unknown source', () => {
    expect(sourceIcon('mystery')).toBe('bolt')
  })
})

describe('actionGloss', () => {
  it('returns the Japanese gloss for a known action in ja', () => {
    expect(actionGloss('organization.renamed', 'ja')).toBe('組織名を変更')
  })

  it('returns null for a known action when the locale is not ja', () => {
    expect(actionGloss('organization.renamed', 'en')).toBeNull()
  })

  it('returns null for an unregistered action even in ja', () => {
    expect(actionGloss('organization.exploded', 'ja')).toBeNull()
  })
})

describe('shortId', () => {
  it('returns short values unchanged', () => {
    expect(shortId('abc')).toBe('abc')
  })

  it('returns a 16-char value unchanged (boundary)', () => {
    const sixteen = '0123456789abcdef'
    expect(sixteen).toHaveLength(16)
    expect(shortId(sixteen)).toBe(sixteen)
  })

  it('truncates a value longer than 16 chars with a middle ellipsis', () => {
    const ulid = '01J8XR0G7Q9V2H7K3N5M0B8TCA' // 26 chars
    expect(shortId(ulid)).toBe('01J8XR0G7…8TCA')
  })
})

describe('absoluteTime', () => {
  it('formats an ISO timestamp as UTC minutes precision', () => {
    expect(absoluteTime('2026-07-18T04:05:06Z')).toBe('2026-07-18 04:05 UTC')
  })

  it('zero-pads month, day, hour, and minute', () => {
    expect(absoluteTime('2026-01-02T03:04:00Z')).toBe('2026-01-02 03:04 UTC')
  })

  it('renders in UTC regardless of the offset in the input', () => {
    // 09:00+09:00 == 00:00 UTC
    expect(absoluteTime('2026-07-18T09:00:00+09:00')).toBe('2026-07-18 00:00 UTC')
  })
})

describe('relativeTime', () => {
  const now = Date.parse('2026-07-18T12:00:00Z')
  const ago = (ms: number) => new Date(now - ms).toISOString()
  const MIN = 60_000
  const HOUR = 60 * MIN
  const DAY = 24 * HOUR

  it('renders sub-minute deltas as "just now"', () => {
    expect(relativeTime(ago(30_000), 'en', now)).toBe('just now')
    expect(relativeTime(ago(30_000), 'ja', now)).toBe('たった今')
  })

  it('renders minutes', () => {
    expect(relativeTime(ago(5 * MIN), 'en', now)).toBe('5m ago')
    expect(relativeTime(ago(5 * MIN), 'ja', now)).toBe('5分前')
  })

  it('renders hours', () => {
    expect(relativeTime(ago(3 * HOUR), 'en', now)).toBe('3h ago')
    expect(relativeTime(ago(3 * HOUR), 'ja', now)).toBe('3時間前')
  })

  it('renders days', () => {
    expect(relativeTime(ago(2 * DAY), 'en', now)).toBe('2d ago')
    expect(relativeTime(ago(2 * DAY), 'ja', now)).toBe('2日前')
  })

  it('clamps a future timestamp to "just now" (never negative)', () => {
    const future = new Date(now + 10 * MIN).toISOString()
    expect(relativeTime(future, 'en', now)).toBe('just now')
  })
})
