import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useAppLauncher } from './use-app-launcher'

describe('useAppLauncher', () => {
  it('loads and maps the installed apps', async () => {
    const { result } = renderHookWithProviders(() => useAppLauncher())

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    expect(result.current.isError).toBe(false)
    expect(result.current.apps).toHaveLength(2)
    expect(result.current.apps[0]?.catalogId).toBe('nene-invoice')
    expect(result.current.apps[0]?.ssotRole).toBe('billing')
    expect(result.current.apps[1]?.publicUrl).toBe('https://example.com/nene-clear/')
  })
})
