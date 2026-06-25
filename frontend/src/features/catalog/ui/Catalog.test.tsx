import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { Catalog } from './Catalog'

describe('Catalog', () => {
  it('lists catalog apps with derived state, dependency hints, and status filtering', async () => {
    const user = userEvent.setup()
    renderWithProviders(<Catalog />)

    // catalog: invoice + clear (both installed via the default installed-apps handler) + vault (planned)
    expect(await screen.findByText('NeNe Invoice')).toBeInTheDocument()
    expect(screen.getByText('NeNe Vault')).toBeInTheDocument()
    expect(screen.getAllByText('Active').length).toBeGreaterThanOrEqual(2)
    expect(screen.getByText('Requires NeNe Invoice')).toBeInTheDocument()

    // filter to the planned chip → only the planned app remains
    await user.click(screen.getByRole('button', { name: 'Coming soon' }))
    expect(screen.queryByText('NeNe Invoice')).not.toBeInTheDocument()
    expect(screen.getByText('NeNe Vault')).toBeInTheDocument()
  })

  it('offers Get for installable apps that are not installed', async () => {
    mswServer.use(http.get('/api/v1/installed-apps', () => HttpResponse.json({ apps: [] })))
    renderWithProviders(<Catalog />)

    expect(await screen.findByText('NeNe Invoice')).toBeInTheDocument()
    expect(screen.getAllByRole('link', { name: 'Get' }).length).toBeGreaterThanOrEqual(2)
  })

  it('shows the installed and available version mirror when present', async () => {
    mswServer.use(
      http.get('/api/v1/catalog/apps', () =>
        HttpResponse.json({
          version: 1,
          apps: [
            {
              id: 'nene-invoice',
              name: 'NeNe Invoice',
              status: 'installable',
              requires: [],
              provides: ['billing-api'],
              installedVersion: '1.3.0',
              availableVersion: '1.4.0',
            },
          ],
        }),
      ),
    )
    renderWithProviders(<Catalog />)

    expect(await screen.findByText('NeNe Invoice')).toBeInTheDocument()
    expect(screen.getByText('Installed 1.3.0 · Latest 1.4.0')).toBeInTheDocument()
  })
})
