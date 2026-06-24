import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AccountView } from './AccountView'

function seedSession(): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: '2026-12-31T00:00:00Z',
    operator: { id: '01J8XR0G7Q9V2H7K3N5M0B8TCA', email: 'superadmin@nene.dev', displayName: null },
    orgExternalId: null,
    role: null,
    superadmin: true,
  })
}

describe('AccountView', () => {
  it('shows the profile identity and switches to security and sessions', async () => {
    const user = userEvent.setup()
    seedSession()
    renderWithProviders(<AccountView />)

    // profile tab (default): real identity
    expect(screen.getByText('superadmin@nene.dev')).toBeInTheDocument()
    expect(screen.getByText('Superadmin')).toBeInTheDocument()

    // security tab: Phase B placeholder
    await user.click(screen.getByRole('tab', { name: 'Security' }))
    expect(screen.getByText(/Password change and multi-factor/)).toBeInTheDocument()

    // sessions tab: real log out action
    await user.click(screen.getByRole('tab', { name: 'Sessions' }))
    expect(screen.getByRole('button', { name: /Log out/ })).toBeInTheDocument()
  })
})
