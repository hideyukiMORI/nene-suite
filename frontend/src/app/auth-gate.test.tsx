import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import { authStore, type AuthSession } from '@/entities/auth'
import { RequireSuperadmin } from './auth-gate'

function session(superadmin: boolean): AuthSession {
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
    superadmin,
  }
}

function renderGate() {
  return render(
    <MemoryRouter initialEntries={['/admin/organizations']}>
      <Routes>
        <Route element={<RequireSuperadmin />}>
          <Route path="/admin/organizations" element={<div>CONSOLE</div>} />
        </Route>
        <Route path="/" element={<div>HOME</div>} />
      </Routes>
    </MemoryRouter>,
  )
}

describe('RequireSuperadmin', () => {
  it('renders the guarded route for a superadmin', () => {
    authStore.setSession(session(true))

    renderGate()

    expect(screen.getByText('CONSOLE')).toBeInTheDocument()
  })

  it('redirects a non-superadmin to home', () => {
    authStore.setSession(session(false))

    renderGate()

    expect(screen.getByText('HOME')).toBeInTheDocument()
  })
})
