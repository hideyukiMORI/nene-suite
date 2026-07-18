import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { afterEach, describe, expect, it } from 'vitest'
import { authStore, type AuthSession, type OrgRole } from '@/entities/auth'
import { RequireAuth, RequireSuperadmin } from './auth-gate'

/**
 * Role × route authorization regression matrix (T3 groundwork).
 *
 * Mirrors the guard nesting of `router.tsx` — RequireSuperadmin composed *inside*
 * RequireAuth — with sentinel leaves, so the matrix exercises the fail-closed
 * guards themselves (not the page bodies) across every auth state. It is a
 * regression net for the existing controls, NOT a penetration test.
 */

// Route inventory mirrored from router.tsx (patterns for registration).
const AUTHED_ROUTE_PATTERNS = [
  '/catalog',
  '/install',
  '/account',
  '/settings',
  '/admin/audit-events',
  '/help',
  '/help/glossary',
  '/help/:slug',
]
const SUPERADMIN_ROUTE_PATTERNS = ['/admin/organizations', '/admin/organizations/:id/memberships']

// Concrete paths to navigate to (dynamic segments filled in).
const AUTHED_PATHS = [
  '/',
  '/catalog',
  '/install',
  '/account',
  '/settings',
  '/admin/audit-events',
  '/help',
  '/help/glossary',
  '/help/tenancy',
]
const SUPERADMIN_PATHS = ['/admin/organizations', '/admin/organizations/o1/memberships']

function session(overrides: {
  superadmin: boolean
  role?: OrgRole | null
  expired?: boolean
}): AuthSession {
  const ttl = overrides.expired === true ? -1000 : 3600 * 1000
  return {
    token: 'test-token',
    expiresAt: new Date(Date.now() + ttl).toISOString(),
    operator: {
      id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
      email: 'operator@example.com',
      displayName: null,
    },
    orgExternalId: null,
    role: overrides.role ?? null,
    superadmin: overrides.superadmin,
  }
}

type AuthState = 'anonymous' | 'member' | 'superadmin' | 'expired'

function applyAuth(state: AuthState): void {
  switch (state) {
    case 'anonymous':
      authStore.clearSession()
      break
    case 'member':
      authStore.setSession(session({ superadmin: false, role: 'member' }))
      break
    case 'superadmin':
      authStore.setSession(session({ superadmin: true, role: 'admin' }))
      break
    case 'expired':
      authStore.setSession(session({ superadmin: true, expired: true }))
      break
  }
}

/** Render the guard tree at `path`; returns the sentinel label that won. */
function outcomeAt(path: string): string {
  render(
    <MemoryRouter initialEntries={[path]}>
      <Routes>
        <Route path="/login" element={<div>LOGIN</div>} />
        <Route element={<RequireAuth />}>
          <Route path="/" element={<div>HOME</div>} />
          {AUTHED_ROUTE_PATTERNS.map((p) => (
            <Route key={p} path={p} element={<div>AUTHED</div>} />
          ))}
          <Route element={<RequireSuperadmin />}>
            {SUPERADMIN_ROUTE_PATTERNS.map((p) => (
              <Route key={p} path={p} element={<div>SUPERADMIN</div>} />
            ))}
          </Route>
        </Route>
        <Route path="*" element={<div>NOTFOUND</div>} />
      </Routes>
    </MemoryRouter>,
  )
  for (const label of ['LOGIN', 'HOME', 'AUTHED', 'SUPERADMIN', 'NOTFOUND']) {
    if (screen.queryByText(label) !== null) return label
  }
  return 'NONE'
}

afterEach(() => {
  authStore.clearSession()
})

describe('route authz matrix — anonymous is sent to /login for every protected route', () => {
  it.each([...AUTHED_PATHS, ...SUPERADMIN_PATHS])('%s → LOGIN', (path) => {
    applyAuth('anonymous')
    expect(outcomeAt(path)).toBe('LOGIN')
  })
})

describe('route authz matrix — an expired session fails closed to /login', () => {
  it.each(['/', '/catalog', '/admin/organizations'])('%s → LOGIN', (path) => {
    applyAuth('expired')
    expect(outcomeAt(path)).toBe('LOGIN')
  })
})

describe('route authz matrix — an authenticated member', () => {
  it.each(AUTHED_PATHS)('reaches authed route %s', (path) => {
    applyAuth('member')
    expect(['HOME', 'AUTHED']).toContain(outcomeAt(path))
  })

  it.each(SUPERADMIN_PATHS)('is redirected home from superadmin route %s', (path) => {
    applyAuth('member')
    expect(outcomeAt(path)).toBe('HOME')
  })
})

describe('route authz matrix — a superadmin reaches everything', () => {
  it.each(AUTHED_PATHS)('reaches authed route %s', (path) => {
    applyAuth('superadmin')
    expect(['HOME', 'AUTHED']).toContain(outcomeAt(path))
  })

  it.each(SUPERADMIN_PATHS)('reaches superadmin route %s', (path) => {
    applyAuth('superadmin')
    expect(outcomeAt(path)).toBe('SUPERADMIN')
  })
})
