import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AdminHub } from './AdminHub'

describe('AdminHub', () => {
  it('defaults to the organizations tab and switches to operators and keys', async () => {
    const user = userEvent.setup()
    renderWithProviders(<AdminHub />)

    // organizations tab (default) renders the organization console
    expect(await screen.findByText('Create organization')).toBeInTheDocument()

    // operators tab renders the real operator roster
    await user.click(screen.getByRole('tab', { name: 'Operators' }))
    expect(await screen.findByText('operator@example.com')).toBeInTheDocument()

    // federation keys tab renders an honest placeholder
    await user.click(screen.getByRole('tab', { name: 'Federation keys' }))
    expect(screen.getByText(/Federation signing keys are managed/)).toBeInTheDocument()
  })
})
