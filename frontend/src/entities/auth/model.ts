export interface Operator {
  id: string
  email: string
  displayName: string | null
}

/** Organization-scoped role (ADR 0012). The platform `superadmin` is a separate dimension. */
export type OrgRole = 'admin' | 'member' | 'viewer'

/** One organization the current operator belongs to, with their role in it (switcher option). */
export interface SessionOrganization {
  organizationId: string
  externalId: string
  name: string
  slug: string
  role: OrgRole
}

export interface AuthSession {
  token: string
  expiresAt: string
  operator: Operator
  orgExternalId: string | null
  role: OrgRole | null
  superadmin: boolean
}

const STORAGE_KEY = 'nene-suite-session'

/**
 * Apex operator session, persisted in sessionStorage (tab-scoped; cleared when the tab
 * closes, unlike localStorage, to shrink the bearer token's exposure window — fleet
 * decision 2026-07-14, vault #148 type). The bearer token is read by shared/api/client on
 * every request; an expired session is treated as logged out.
 */
export const authStore = {
  getSession(): AuthSession | null {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (raw === null) {
      return null
    }
    try {
      return JSON.parse(raw) as AuthSession
    } catch (error) {
      if (import.meta.env.DEV) {
        console.warn('[authStore] Failed to parse session from sessionStorage:', error)
      }
      return null
    }
  },

  setSession(session: AuthSession): void {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(session))
  },

  clearSession(): void {
    sessionStorage.removeItem(STORAGE_KEY)
  },

  getToken(): string | null {
    return this.getSession()?.token ?? null
  },

  // Active-org context (A6). Defaults fail closed so a pre-A6 persisted session
  // (which lacks these fields) reads as a non-superadmin with no active org.
  isSuperadmin(): boolean {
    return this.getSession()?.superadmin ?? false
  },

  getRole(): OrgRole | null {
    return this.getSession()?.role ?? null
  },

  getOrgExternalId(): string | null {
    return this.getSession()?.orgExternalId ?? null
  },

  isAuthenticated(): boolean {
    const session = this.getSession()
    if (session === null) {
      return false
    }
    return new Date(session.expiresAt) > new Date()
  },
}
