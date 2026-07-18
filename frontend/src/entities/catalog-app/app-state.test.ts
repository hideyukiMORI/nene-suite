import { describe, expect, it } from 'vitest'
import {
  CARD_STATE_META,
  CARD_STATE_ORDER,
  type CatalogCardState,
  deriveCardState,
} from './app-state'
import type { CatalogApp, CatalogAppStatus } from './model'

function app(overrides: Partial<CatalogApp> = {}): CatalogApp {
  return {
    id: 'nene-invoice',
    name: 'NeNe Invoice',
    status: 'installable',
    requires: [],
    provides: [],
    installedVersion: null,
    availableVersion: null,
    ...overrides,
  }
}

describe('deriveCardState', () => {
  it('is "installed" when the app id is in the installed set', () => {
    const state = deriveCardState(app({ id: 'nene-invoice' }), new Set(['nene-invoice']))

    expect(state).toBe('installed')
  })

  it('reflects the catalog status when the app is not installed', () => {
    const cases: Array<[CatalogAppStatus, CatalogCardState]> = [
      ['installable', 'installable'],
      ['planned', 'planned'],
      ['deprecated', 'deprecated'],
    ]

    for (const [status, expected] of cases) {
      expect(deriveCardState(app({ status }), new Set())).toBe(expected)
    }
  })

  it('lets "installed" win over the catalog status (a deprecated-but-installed app reads installed)', () => {
    const state = deriveCardState(app({ id: 'legacy', status: 'deprecated' }), new Set(['legacy']))

    expect(state).toBe('installed')
  })

  it('does not treat a different installed id as this app', () => {
    const state = deriveCardState(app({ id: 'nene-invoice' }), new Set(['nene-vault']))

    expect(state).toBe('installable')
  })
})

describe('CARD_STATE_META', () => {
  const ALL_STATES: CatalogCardState[] = ['installed', 'installable', 'planned', 'deprecated']

  it('has a label key and a tone for every card state', () => {
    for (const state of ALL_STATES) {
      expect(CARD_STATE_META[state].labelKey).toBeTruthy()
      expect(CARD_STATE_META[state].tone).toMatch(/^var\(--/)
    }
  })

  it('defines exactly the card states — no extras', () => {
    expect(Object.keys(CARD_STATE_META).sort()).toEqual([...ALL_STATES].sort())
  })
})

describe('CARD_STATE_ORDER', () => {
  it('lists installable first (the primary call to action)', () => {
    expect(CARD_STATE_ORDER[0]).toBe('installable')
  })

  it('covers every card state exactly once', () => {
    expect(CARD_STATE_ORDER).toHaveLength(4)
    expect(new Set(CARD_STATE_ORDER).size).toBe(4)
    expect([...CARD_STATE_ORDER].sort()).toEqual(Object.keys(CARD_STATE_META).sort())
  })
})
