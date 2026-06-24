import { renderHook } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { useAppNav } from './use-app-nav'

describe('useAppNav', () => {
  it('returns the five primary destinations in order', () => {
    const { result } = renderHook(() => useAppNav(), { wrapper: I18nProvider })
    expect(result.current.map((item) => item.id)).toEqual([
      'home',
      'catalog',
      'audit',
      'admin',
      'settings',
    ])
    expect(result.current.map((item) => item.path)).toEqual([
      '/',
      '/catalog',
      '/admin/audit-events',
      '/admin/organizations',
      '/settings',
    ])
  })
})
