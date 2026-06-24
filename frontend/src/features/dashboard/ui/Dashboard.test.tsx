import { screen } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { Dashboard } from './Dashboard'

function seedSession(): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: { id: '01J8XR0G7Q9V2H7K3N5M0B8TCA', email: 'superadmin@nene.dev', displayName: null },
    orgExternalId: null,
    role: null,
    superadmin: true,
  })
}

describe('Dashboard', () => {
  it('renders the masthead, installed-apps count, and pillars', async () => {
    seedSession()
    renderWithProviders(<Dashboard />)

    expect(await screen.findByText('NeNe Invoice')).toBeInTheDocument()
    expect(screen.getByText('NeNe Clear')).toBeInTheDocument()
    expect(screen.getByText('2')).toBeInTheDocument()
    expect(screen.getByText('superadmin')).toBeInTheDocument()
    expect(screen.getByText('Available updates')).toBeInTheDocument()
  })

  it('shows the first-run welcome when no apps are installed', async () => {
    seedSession()
    mswServer.use(http.get('/api/v1/installed-apps', () => HttpResponse.json({ apps: [] })))
    renderWithProviders(<Dashboard />)

    expect(await screen.findByText('Welcome to NeNe Suite')).toBeInTheDocument()
  })

  it('wires the updates panel to verified Origin signals', async () => {
    seedSession()
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
              changelogUrl: null,
              releasedAt: null,
              freshness: 'fresh',
              reason: null,
              warnings: [],
            },
            {
              product: 'nene-clear',
              channel: 'stable',
              installedVersion: '1.0.0',
              status: 'update_available',
              latestVersion: '1.1.0',
              minSupportedVersion: null,
              changelogUrl: null,
              releasedAt: null,
              freshness: 'fresh',
              reason: null,
              warnings: [],
            },
          ],
        }),
      ),
    )
    renderWithProviders(<Dashboard />)

    expect(await screen.findByText('Latest 1.4.0')).toBeInTheDocument()
    expect(screen.getByText('Update available')).toBeInTheDocument()
    expect(screen.getByText('Latest available')).toBeInTheDocument()
  })
})
