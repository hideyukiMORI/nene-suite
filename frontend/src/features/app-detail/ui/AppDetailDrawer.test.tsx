import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AppDetailDrawer } from './AppDetailDrawer'

describe('AppDetailDrawer', () => {
  it('renders details for an installed app with an Open action and the Origin changelog placeholder', async () => {
    renderWithProviders(<AppDetailDrawer appId="nene-invoice" onClose={() => {}} />)

    expect(await screen.findByRole('heading', { name: 'NeNe Invoice' })).toBeInTheDocument()
    expect(screen.getByText('Change history')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Open' })).toBeInTheDocument()
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
