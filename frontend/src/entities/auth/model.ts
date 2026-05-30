export interface Operator {
  id: string
  email: string
  displayName: string | null
}

export interface AuthSession {
  token: string
  expiresAt: string
  operator: Operator
}

const STORAGE_KEY = 'nene-suite-session'

/**
 * Apex operator session, persisted in localStorage. The bearer token is read by
 * shared/api/client on every request; an expired session is treated as logged out.
 */
export const authStore = {
  getSession(): AuthSession | null {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw === null) {
      return null
    }
    try {
      return JSON.parse(raw) as AuthSession
    } catch (error) {
      if (import.meta.env.DEV) {
        console.warn('[authStore] Failed to parse session from localStorage:', error)
      }
      return null
    }
  },

  setSession(session: AuthSession): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session))
  },

  clearSession(): void {
    localStorage.removeItem(STORAGE_KEY)
  },

  getToken(): string | null {
    return this.getSession()?.token ?? null
  },

  isAuthenticated(): boolean {
    const session = this.getSession()
    if (session === null) {
      return false
    }
    return new Date(session.expiresAt) > new Date()
  },
}
