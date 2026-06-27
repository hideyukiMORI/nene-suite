import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import type { CatalogApp } from '@/entities/catalog-app'
import type { InstallSession } from '@/entities/install-session'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { ReviewStep } from './ReviewStep'

function app(id: string, name: string): CatalogApp {
  return {
    id,
    name,
    status: 'installable',
    requires: [],
    provides: [],
    installedVersion: null,
    availableVersion: null,
  }
}

function reviewSession(): InstallSession {
  return {
    id: 's1',
    suiteId: 'suite',
    status: 'in_progress',
    tier: 'B',
    catalogRevision: 1,
    selectedApps: ['nene-invoice', 'nene-clear'],
    databaseTargets: [
      {
        catalogId: 'nene-invoice',
        mode: 'adopt',
        server: 'legacy-db.internal',
        name: 'invoice_prod',
      },
      { catalogId: 'nene-clear', mode: 'provision', server: null, name: null },
    ],
    disclaimerAccepted: true,
    disclaimerAcceptedAt: null,
    orgExternalId: null,
    orgDisplayName: 'Acme KK',
    installManifestId: null,
    failureCode: null,
    createdAt: '',
    updatedAt: '',
    completedAt: null,
  }
}

describe('ReviewStep', () => {
  it('echoes friendly app names, database targets, the org, and integrations', () => {
    renderWithProviders(
      <ReviewStep
        session={reviewSession()}
        apps={[app('nene-invoice', 'NeNe Invoice'), app('nene-clear', 'NeNe Clear')]}
        isPending={false}
        onComplete={vi.fn()}
      />,
    )

    // friendly names, not internal ids
    expect(screen.getByText('NeNe Invoice')).toBeInTheDocument()
    expect(screen.getByText('NeNe Clear')).toBeInTheDocument()
    expect(screen.queryByText('nene-invoice')).not.toBeInTheDocument()

    // the adopted database target echoes its name + server
    expect(screen.getByText(/invoice_prod/)).toBeInTheDocument()
    expect(screen.getByText(/legacy-db\.internal/)).toBeInTheDocument()

    // organization name and the Clear → Invoice integration are surfaced
    expect(screen.getByText('Acme KK')).toBeInTheDocument()
    expect(screen.getByText(/Clear → Invoice/)).toBeInTheDocument()
  })
})
