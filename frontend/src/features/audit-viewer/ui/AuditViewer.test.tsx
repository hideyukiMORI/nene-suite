import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AuditViewer } from './AuditViewer'

describe('AuditViewer', () => {
  it('renders audit rows and an export button', async () => {
    renderWithProviders(<AuditViewer />)

    expect(await screen.findByText('organization.renamed')).toBeInTheDocument()
    expect(screen.getByText('membership.granted')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Export CSV/ })).toBeInTheDocument()
  })

  it('filters the list by change type', async () => {
    const user = userEvent.setup()
    renderWithProviders(<AuditViewer />)
    await screen.findByText('organization.renamed')

    await user.click(screen.getByRole('tab', { name: /Created/ }))

    // create events remain; the update event is filtered out
    expect(screen.getByText('membership.granted')).toBeInTheDocument()
    expect(screen.queryByText('organization.renamed')).not.toBeInTheDocument()
  })

  it('opens the detail drawer with a before/after diff on row click', async () => {
    const user = userEvent.setup()
    renderWithProviders(<AuditViewer />)
    await screen.findByText('organization.renamed')

    await user.click(screen.getByRole('button', { name: /organization\.renamed/ }))

    // drawer meta + the changed value rendered in the diff
    expect(screen.getByText('Suite id')).toBeInTheDocument()
    expect(screen.getByText('"Acme Corporation KK"')).toBeInTheDocument()
  })
})
