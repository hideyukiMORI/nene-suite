import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useMembershipConsole } from './use-membership-console'

const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC'

describe('useMembershipConsole', () => {
  it('loads the enriched member list', async () => {
    const { result } = renderHookWithProviders(() => useMembershipConsole(ORG_ID))

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    expect(result.current.members).toHaveLength(1)
    expect(result.current.members[0]?.email).toBe('operator@example.com')
    expect(result.current.members[0]?.role).toBe('admin')
  })
})
