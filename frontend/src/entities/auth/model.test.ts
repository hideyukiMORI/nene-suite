import { describe, expect, it } from 'vitest'
import { authStore, type AuthSession } from './model'

const STORAGE_KEY = 'nene-suite-session'

function session(overrides: Partial<AuthSession> = {}): AuthSession {
  return {
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: {
      id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
      email: 'operator@example.com',
      displayName: null,
    },
    orgExternalId: null,
    role: null,
    superadmin: false,
    ...overrides,
  }
}

describe('authStore', () => {
  it('persists the session in sessionStorage', () => {
    authStore.setSession(session())

    expect(sessionStorage.getItem(STORAGE_KEY)).not.toBeNull()
    expect(authStore.getSession()?.token).toBe('test-token')
  })

  // Fleet decision 2026-07-14 (vault #148 type): the bearer token must never be
  // written to localStorage, which persists across tabs/windows and widens the
  // token's exposure window on XSS. sessionStorage is tab-scoped and cleared on
  // tab close.
  it('never writes the session to localStorage', () => {
    authStore.setSession(session())

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull()
  })

  it('clears the session from sessionStorage', () => {
    authStore.setSession(session())
    authStore.clearSession()

    expect(sessionStorage.getItem(STORAGE_KEY)).toBeNull()
    expect(authStore.getSession()).toBeNull()
  })

  it('treats a session past expiresAt as not authenticated', () => {
    authStore.setSession(session({ expiresAt: new Date(Date.now() - 1000).toISOString() }))

    expect(authStore.isAuthenticated()).toBe(false)
  })
})
