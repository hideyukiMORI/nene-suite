import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { DatabaseStep } from './DatabaseStep'

const apps = [
  { id: 'nene-invoice', name: 'NeNe Invoice' },
  { id: 'nene-clear', name: 'NeNe Clear' },
]

describe('DatabaseStep', () => {
  it('submits provision for every app by default', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn()
    renderWithProviders(
      <DatabaseStep apps={apps} isPending={false} onSubmit={onSubmit} onBack={() => {}} />,
    )

    await user.click(screen.getByRole('button', { name: 'Next' }))

    expect(onSubmit).toHaveBeenCalledWith([
      { catalogId: 'nene-invoice', mode: 'provision' },
      { catalogId: 'nene-clear', mode: 'provision' },
    ])
  })

  it('reveals adopt fields and submits server + name for the adopted app', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn()
    renderWithProviders(
      <DatabaseStep apps={apps} isPending={false} onSubmit={onSubmit} onBack={() => {}} />,
    )

    await user.click(
      within(screen.getByRole('radiogroup', { name: 'Database mode for NeNe Invoice' })).getByRole(
        'radio',
        { name: 'Adopt existing' },
      ),
    )
    await user.type(screen.getByPlaceholderText('Suite server (default)'), 'legacy-db.internal')
    await user.type(screen.getByPlaceholderText('Suite convention (default)'), 'invoice_prod')
    await user.click(screen.getByRole('button', { name: 'Next' }))

    expect(onSubmit).toHaveBeenCalledWith([
      {
        catalogId: 'nene-invoice',
        mode: 'adopt',
        server: 'legacy-db.internal',
        name: 'invoice_prod',
      },
      { catalogId: 'nene-clear', mode: 'provision' },
    ])
  })

  it('omits empty adopt server/name (suite-server / convention defaults)', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn()
    renderWithProviders(
      <DatabaseStep apps={apps} isPending={false} onSubmit={onSubmit} onBack={() => {}} />,
    )

    await user.click(
      within(screen.getByRole('radiogroup', { name: 'Database mode for NeNe Clear' })).getByRole(
        'radio',
        { name: 'Adopt existing' },
      ),
    )
    await user.click(screen.getByRole('button', { name: 'Next' }))

    expect(onSubmit).toHaveBeenCalledWith([
      { catalogId: 'nene-invoice', mode: 'provision' },
      { catalogId: 'nene-clear', mode: 'adopt' },
    ])
  })

  it('disables Next when there are no apps (e.g. the session is still loading)', () => {
    renderWithProviders(
      <DatabaseStep apps={[]} isPending={false} onSubmit={vi.fn()} onBack={() => {}} />,
    )

    expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled()
  })
})
