import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AppDetailDrawer } from './AppDetailDrawer'

describe('AppDetailDrawer', () => {
  it('renders details for an installed app with an Open action and the Origin changelog placeholder', async () => {
    renderWithProviders(<AppDetailDrawer appId="nene-invoice" onClose={() => {}} />)

    expect(await screen.findByRole('heading', { name: 'NeNe Invoice' })).toBeInTheDocument()
    expect(screen.getByText('Change history')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Open' })).toBeInTheDocument()
  })

  it('wires the changelog to the verified Origin update signal', async () => {
    mswServer.use(
      http.get('/api/v1/origin/updates', () =>
        HttpResponse.json({
          available: true,
          updates: [
            {
              product: 'nene-invoice',
              channel: 'stable',
              installedVersion: null,
              status: 'unknown',
              latestVersion: '1.4.0',
              minSupportedVersion: '1.2.0',
              changelogUrl: 'https://cdn.nene-origin.dev/nene-invoice/changelog/1.4.0',
              releasedAt: null,
              freshness: 'fresh',
              reason: null,
              warnings: [],
            },
          ],
        }),
      ),
    )
    renderWithProviders(<AppDetailDrawer appId="nene-invoice" onClose={() => {}} />)

    expect(await screen.findByText('Latest 1.4.0')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /View change history/ })).toBeInTheDocument()
  })

  it('invokes onClose on Escape', async () => {
    const user = userEvent.setup()
    const onClose = vi.fn()
    renderWithProviders(<AppDetailDrawer appId="nene-invoice" onClose={onClose} />)

    await screen.findByRole('heading', { name: 'NeNe Invoice' })
    await user.keyboard('{Escape}')

    expect(onClose).toHaveBeenCalled()
  })
})
