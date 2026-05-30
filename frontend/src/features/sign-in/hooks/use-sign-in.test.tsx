import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useSignIn } from './use-sign-in'

describe('useSignIn', () => {
  it('persists the session on valid credentials', async () => {
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.submit({ email: 'operator@example.com', password: 's3cret-pass' })
    })

    await waitFor(() => {
      expect(authStore.isAuthenticated()).toBe(true)
    })
    expect(authStore.getSession()?.operator.email).toBe('operator@example.com')
  })

  it('flags invalid credentials without persisting a session', async () => {
    const { result } = renderHookWithProviders(() => useSignIn())

    act(() => {
      result.current.submit({ email: 'operator@example.com', password: 'wrong' })
    })

    await waitFor(() => {
      expect(result.current.isInvalidCredentials).toBe(true)
    })
    expect(authStore.getSession()).toBeNull()
  })
})
