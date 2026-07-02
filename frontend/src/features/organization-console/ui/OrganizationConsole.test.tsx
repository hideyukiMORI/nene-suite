import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { OrganizationConsole } from './OrganizationConsole'

function actionsButtonFor(rowIndex: number): HTMLElement {
  const buttons = screen.getAllByRole('button', { name: 'Actions' })
  const button = buttons[rowIndex]
  if (button === undefined) throw new Error(`no Actions button at row ${String(rowIndex)}`)
  return button
}

describe('OrganizationConsole', () => {
  it('renders the organization list', async () => {
    renderWithProviders(<OrganizationConsole />)

    expect(await screen.findByText('Acme KK')).toBeInTheDocument()
    expect(screen.getByText('acme-kk')).toBeInTheDocument()
    expect(screen.getByText('Umbrella KK')).toBeInTheDocument()
  })

  it('rejects an invalid slug client-side with the concrete rule', async () => {
    const user = userEvent.setup()
    renderWithProviders(<OrganizationConsole />)
    await screen.findByText('Acme KK')

    await user.type(screen.getByPlaceholderText('Acme KK'), 'Bad Org')
    await user.type(screen.getByPlaceholderText('acme-kk'), 'Bad Slug!')
    await user.click(screen.getByRole('button', { name: 'Create' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/hyphen-separated segments/)
  })

  it('filters the organization list by name or slug', async () => {
    const user = userEvent.setup()
    renderWithProviders(<OrganizationConsole />)
    await screen.findByText('Acme KK')

    await user.type(screen.getByRole('searchbox', { name: /Filter by name or slug/ }), 'umbrella')

    expect(screen.getByText('Umbrella KK')).toBeInTheDocument()
    expect(screen.queryByText('Acme KK')).not.toBeInTheDocument()

    await user.clear(screen.getByRole('searchbox', { name: /Filter by name or slug/ }))
    await user.type(screen.getByRole('searchbox', { name: /Filter by name or slug/ }), 'zzz')

    expect(screen.getByText('No organizations match your filter.')).toBeInTheDocument()
  })

  it('surfaces a slug-conflict message when create fails', async () => {
    const user = userEvent.setup()
    renderWithProviders(<OrganizationConsole />)
    await screen.findByText('Acme KK')

    await user.type(screen.getByPlaceholderText('Acme KK'), 'Dup Org')
    await user.type(screen.getByPlaceholderText('acme-kk'), 'taken')
    await user.click(screen.getByRole('button', { name: 'Create' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('already exists')
  })

  it('requires confirmation with impact and reversibility copy before disabling', async () => {
    const user = userEvent.setup()
    renderWithProviders(<OrganizationConsole />)
    await screen.findByText('Acme KK')

    await user.click(actionsButtonFor(0))
    await user.click(screen.getByRole('menuitem', { name: 'Disable' }))

    // a confirmation step appears instead of disabling immediately,
    // spelling out that disable is a reversible freeze — not deletion
    expect(screen.getByText('Disable this organization?')).toBeInTheDocument()
    expect(screen.getByText(/reversible freeze, not deletion/)).toBeInTheDocument()
    expect(screen.getByText(/recorded in the audit log/)).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Cancel' }))
    expect(screen.queryByText('Disable this organization?')).not.toBeInTheDocument()
  })

  it('re-enables a disabled organization through the confirm step', async () => {
    const user = userEvent.setup()
    let enableCalls = 0
    mswServer.use(
      http.post('/api/v1/organizations/:id/enable', ({ params }) => {
        enableCalls += 1
        return HttpResponse.json({
          id: String(params.id),
          externalId: `${String(params.id)}EXT`,
          name: 'Umbrella KK',
          slug: 'umbrella-kk',
          status: 'active',
        })
      }),
    )
    renderWithProviders(<OrganizationConsole />)
    await screen.findByText('Umbrella KK')

    await user.click(actionsButtonFor(1))
    await user.click(screen.getByRole('menuitem', { name: 'Re-enable' }))

    expect(screen.getByText('Re-enable this organization?')).toBeInTheDocument()
    expect(screen.getByText(/Members can sign in again/)).toBeInTheDocument()

    await user.click(screen.getByRole('menuitem', { name: 'Re-enable' }))

    await waitFor(() => {
      expect(enableCalls).toBe(1)
    })
    expect(screen.queryByText('Re-enable this organization?')).not.toBeInTheDocument()
  })
})
