import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import type { Command } from '../command'
import { CommandPalette } from './CommandPalette'

const labels = {
  title: 'Command palette',
  placeholder: 'Search…',
  empty: 'No matches.',
  navGroup: 'Go to',
  actionGroup: 'Actions',
}

function makeCommands(run: () => void): Command[] {
  return [
    { id: 'nav:home', group: 'nav', label: 'Home', icon: 'home', run },
    { id: 'nav:catalog', group: 'nav', label: 'Catalog', icon: 'apps', run },
    { id: 'action:theme', group: 'action', label: 'Toggle theme', icon: 'contrast', run },
  ]
}

describe('CommandPalette', () => {
  it('lists commands and filters by query', async () => {
    const user = userEvent.setup()
    render(<CommandPalette onClose={() => {}} commands={makeCommands(() => {})} labels={labels} />)

    expect(screen.getByRole('option', { name: /Home/ })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /Toggle theme/ })).toBeInTheDocument()

    await user.type(screen.getByRole('textbox'), 'cat')

    expect(screen.getByRole('option', { name: /Catalog/ })).toBeInTheDocument()
    expect(screen.queryByRole('option', { name: /Home/ })).not.toBeInTheDocument()
  })

  it('runs a command on click and closes', async () => {
    const user = userEvent.setup()
    const run = vi.fn()
    const onClose = vi.fn()
    render(<CommandPalette onClose={onClose} commands={makeCommands(run)} labels={labels} />)

    await user.click(screen.getByRole('option', { name: /Catalog/ }))

    expect(run).toHaveBeenCalledOnce()
    expect(onClose).toHaveBeenCalledOnce()
  })

  it('closes on Escape', async () => {
    const user = userEvent.setup()
    const onClose = vi.fn()
    render(<CommandPalette onClose={onClose} commands={makeCommands(() => {})} labels={labels} />)

    await user.keyboard('{Escape}')

    expect(onClose).toHaveBeenCalledOnce()
  })
})
