import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useOrganizationConsole } from './use-organization-console'

describe('useOrganizationConsole', () => {
  it('loads the organization list', async () => {
    const { result } = renderHookWithProviders(() => useOrganizationConsole())

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    expect(result.current.organizations).toHaveLength(1)
    expect(result.current.organizations[0]?.name).toBe('Acme KK')
    expect(result.current.organizations[0]?.status).toBe('active')
  })
})
