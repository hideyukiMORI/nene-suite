import { renderHook } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth'
import { I18nProvider } from '@/shared/i18n'
import { useAppNavGroups } from './use-app-nav'

function seed(superadmin: boolean): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: { id: '01J8XR0G7Q9V2H7K3N5M0B8TCA', email: 'op@nene.dev', displayName: null },
    orgExternalId: '01J8XRORG000000000000000AA',
    role: 'admin',
    superadmin,
  })
}

describe('useAppNavGroups', () => {
  it('returns operations/governance/support groups with items in order', () => {
    seed(true)
    const { result } = renderHook(() => useAppNavGroups(), { wrapper: I18nProvider })

    expect(result.current.map((group) => group.id)).toEqual(['operations', 'governance', 'support'])
    expect(result.current.flatMap((group) => group.items.map((item) => item.id))).toEqual([
      'home',
      'catalog',
      'install',
      'admin',
      'audit',
      'settings',
      'help',
    ])
  })

  it('drops superadmin-only items for non-superadmins', () => {
    seed(false)
    const { result } = renderHook(() => useAppNavGroups(), { wrapper: I18nProvider })

    const ids = result.current.flatMap((group) => group.items.map((item) => item.id))
    expect(ids).not.toContain('admin')
    expect(ids).toContain('audit')
    // governance group survives on its remaining items
    expect(result.current.map((group) => group.id)).toContain('governance')
  })
})
