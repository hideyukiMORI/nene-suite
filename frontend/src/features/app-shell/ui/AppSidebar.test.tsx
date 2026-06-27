import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { authStore } from '@/entities/auth'
import { SESSION_ORG_ACME } from '@tests/msw/handlers/auth'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AppSidebar } from './AppSidebar'

function seedSession(superadmin: boolean): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: { id: '01J8XR0G7Q9V2H7K3N5M0B8TCA', email: 'op@nene.dev', displayName: null },
    orgExternalId: SESSION_ORG_ACME.externalId,
    role: 'admin',
    superadmin,
  })
}

describe('AppSidebar', () => {
  it('renders the brand and grouped navigation', () => {
    seedSession(true)
    renderWithProviders(<AppSidebar open={false} onClose={() => {}} />)

    expect(screen.getByText('NeNe Suite')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Home/ })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Audit log/ })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Organizations/ })).toBeInTheDocument()
  })

  it('hides the superadmin-only Organizations link from non-superadmins', () => {
    seedSession(false)
    renderWithProviders(<AppSidebar open={false} onClose={() => {}} />)

    expect(screen.queryByRole('link', { name: /Organizations/ })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Audit log/ })).toBeInTheDocument()
  })

  it('closes the drawer when a nav link is activated', async () => {
    const user = userEvent.setup()
    const onClose = vi.fn()
    seedSession(true)
    renderWithProviders(<AppSidebar open onClose={onClose} />)

    await user.click(screen.getByRole('link', { name: /Catalog/ }))

    expect(onClose).toHaveBeenCalled()
  })
})
