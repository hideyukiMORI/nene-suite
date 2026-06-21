import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { CONFLICT_OPERATOR } from '@tests/msw/handlers/membership'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useChangeMembershipRole, useGrantMembership, useRevokeMembership } from './mutations'

const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC'
const MEM_ID = '01J8XRMEM00000000000000ZAB'

describe('membership mutations', () => {
  it('grants a membership', async () => {
    const { result } = renderHookWithProviders(() => useGrantMembership(ORG_ID))

    act(() => {
      result.current.mutate({ operatorId: '01J8XR0G7Q9V2H7K3N5M0B8TCB', role: 'member' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
  })

  it('surfaces a membership conflict (409) as an AppError', async () => {
    const { result } = renderHookWithProviders(() => useGrantMembership(ORG_ID))

    act(() => {
      result.current.mutate({ operatorId: CONFLICT_OPERATOR, role: 'admin' })
    })

    await waitFor(() => {
      expect(result.current.isError).toBe(true)
    })
    expect(result.current.error?.status).toBe(409)
    expect(result.current.error?.type).toContain('membership-conflict')
  })

  it('changes a member role', async () => {
    const { result } = renderHookWithProviders(() => useChangeMembershipRole(ORG_ID))

    act(() => {
      result.current.mutate({ membershipId: MEM_ID, role: 'viewer' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
  })

  it('revokes a membership', async () => {
    const { result } = renderHookWithProviders(() => useRevokeMembership(ORG_ID))

    act(() => {
      result.current.mutate(MEM_ID)
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
  })
})
