import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useCreateOrganization, useDisableOrganization, useRenameOrganization } from './mutations'

const ORG_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA'

describe('organization mutations', () => {
  it('creates an organization', async () => {
    const { result } = renderHookWithProviders(() => useCreateOrganization())

    act(() => {
      result.current.mutate({ name: 'New Org', slug: 'new-org' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.name).toBe('New Org')
    expect(result.current.data?.status).toBe('active')
  })

  it('surfaces a slug conflict (409) as an AppError', async () => {
    const { result } = renderHookWithProviders(() => useCreateOrganization())

    act(() => {
      result.current.mutate({ name: 'Dup', slug: 'taken' })
    })

    await waitFor(() => {
      expect(result.current.isError).toBe(true)
    })
    expect(result.current.error?.status).toBe(409)
    expect(result.current.error?.type).toContain('organization-slug-conflict')
  })

  it('renames an organization', async () => {
    const { result } = renderHookWithProviders(() => useRenameOrganization())

    act(() => {
      result.current.mutate({ id: ORG_ID, name: 'Renamed KK' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.name).toBe('Renamed KK')
  })

  it('disables an organization', async () => {
    const { result } = renderHookWithProviders(() => useDisableOrganization())

    act(() => {
      result.current.mutate(ORG_ID)
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.status).toBe('disabled')
  })
})
