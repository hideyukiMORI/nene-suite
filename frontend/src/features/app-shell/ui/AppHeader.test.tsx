import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { authStore } from '@/entities/auth'
import { SESSION_ORG_ACME } from '@tests/msw/handlers/auth'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AppHeader } from './AppHeader'

function seedSession(): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: { id: '01J8XR0G7Q9V2H7K3N5M0B8TCA', email: 'superadmin@nene.dev', displayName: null },
    orgExternalId: SESSION_ORG_ACME.externalId,
    role: 'admin',
    superadmin: true,
  })
}

describe('AppHeader', () => {
  it('invokes the command palette from the search trigger', async () => {
    const user = userEvent.setup()
    const onOpenPalette = vi.fn()
    seedSession()
    renderWithProviders(<AppHeader onOpenPalette={onOpenPalette} onOpenMenu={() => {}} />)

    await user.click(screen.getByRole('button', { name: 'Command palette' }))

    expect(onOpenPalette).toHaveBeenCalledOnce()
  })

  it('opens the sidebar drawer from the hamburger', async () => {
    const user = userEvent.setup()
    const onOpenMenu = vi.fn()
    seedSession()
    renderWithProviders(<AppHeader onOpenPalette={() => {}} onOpenMenu={onOpenMenu} />)

    await user.click(screen.getByRole('button', { name: 'Open navigation menu' }))

    expect(onOpenMenu).toHaveBeenCalledOnce()
  })

  it('opens the account menu with a logout action', async () => {
    const user = userEvent.setup()
    seedSession()
    renderWithProviders(<AppHeader onOpenPalette={() => {}} onOpenMenu={() => {}} />)

    await user.click(screen.getByRole('button', { name: 'Account menu' }))

    expect(screen.getByText('superadmin@nene.dev')).toBeInTheDocument()
    expect(screen.getByRole('menuitem', { name: /Log out/ })).toBeInTheDocument()
  })
})
