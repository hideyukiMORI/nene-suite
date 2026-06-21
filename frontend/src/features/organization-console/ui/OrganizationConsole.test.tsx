import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { OrganizationConsole } from './OrganizationConsole'

describe('OrganizationConsole', () => {
  it('renders the organization list', async () => {
    renderWithProviders(<OrganizationConsole />)

    expect(await screen.findByText('Acme KK')).toBeInTheDocument()
    expect(screen.getByText('acme-kk')).toBeInTheDocument()
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
})
