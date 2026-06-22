import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth'
import { SESSION_ORG_ACME, SESSION_ORG_BETA } from '@tests/msw/handlers/auth'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { ActiveOrgIndicator } from './ActiveOrgIndicator'

function seedSession(): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: {
      id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
      email: 'operator@example.com',
      displayName: null,
    },
    orgExternalId: SESSION_ORG_ACME.externalId,
    role: 'admin',
    superadmin: true,
  })
}

describe('ActiveOrgIndicator', () => {
  it('renders the operator organizations with the active one selected', async () => {
    seedSession()
    renderWithProviders(<ActiveOrgIndicator />)

    const select = await screen.findByRole('combobox')
    expect(await screen.findByRole('option', { name: 'Acme KK' })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: 'Beta LLC' })).toBeInTheDocument()
    expect(select).toHaveValue(SESSION_ORG_ACME.organizationId)
  })

  it('switches the active organization and persists the re-issued session', async () => {
    const user = userEvent.setup()
    seedSession()
    renderWithProviders(<ActiveOrgIndicator />)

    const select = await screen.findByRole('combobox')
    await user.selectOptions(select, SESSION_ORG_BETA.organizationId)

    await waitFor(() => {
      expect(authStore.getOrgExternalId()).toBe(SESSION_ORG_BETA.externalId)
    })
  })
})
