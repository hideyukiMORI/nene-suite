import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AuditViewer } from './AuditViewer'

describe('AuditViewer', () => {
  it('renders audit rows, an export button, and a working kind filter chip', async () => {
    const user = userEvent.setup()
    renderWithProviders(<AuditViewer />)

    expect(await screen.findByText('install_session.completed')).toBeInTheDocument()
    expect(screen.getByText('disclaimer.accepted')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Export CSV/ })).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'install_session' }))

    expect(screen.getByRole('button', { name: /Clear/ })).toBeInTheDocument()
    expect(screen.getByText('install_session.completed')).toBeInTheDocument()
  })
})
