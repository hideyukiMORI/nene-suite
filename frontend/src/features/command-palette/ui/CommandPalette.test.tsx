import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'
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

    await user.type(screen.getByRole('combobox'), 'cat')

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

  it('exposes the combobox pattern and follows arrow keys via aria-activedescendant', async () => {
    const user = userEvent.setup()
    render(<CommandPalette onClose={() => {}} commands={makeCommands(() => {})} labels={labels} />)

    const input = screen.getByRole('combobox', { name: 'Command palette' })
    expect(input).toHaveFocus()
    expect(input).toHaveAttribute('aria-expanded', 'true')
    expect(input).toHaveAttribute('aria-autocomplete', 'list')
    expect(input.getAttribute('aria-controls')).toBe(screen.getByRole('listbox').id)

    const home = screen.getByRole('option', { name: /Home/ })
    expect(input).toHaveAttribute('aria-activedescendant', home.id)
    expect(home).toHaveAttribute('aria-selected', 'true')

    await user.keyboard('{ArrowDown}')
    const catalog = screen.getByRole('option', { name: /Catalog/ })
    expect(input).toHaveAttribute('aria-activedescendant', catalog.id)
    expect(catalog).toHaveAttribute('aria-selected', 'true')
    expect(home).toHaveAttribute('aria-selected', 'false')
  })

  it('announces the empty state and drops aria-activedescendant without matches', async () => {
    const user = userEvent.setup()
    render(<CommandPalette onClose={() => {}} commands={makeCommands(() => {})} labels={labels} />)

    await user.type(screen.getByRole('combobox'), 'nomatch')

    expect(screen.getByRole('status')).toHaveTextContent('No matches.')
    expect(screen.getByRole('combobox')).not.toHaveAttribute('aria-activedescendant')
    expect(screen.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false')
  })

  it('keeps focus on the input when tabbing', async () => {
    const user = userEvent.setup()
    render(<CommandPalette onClose={() => {}} commands={makeCommands(() => {})} labels={labels} />)

    await user.tab()
    expect(screen.getByRole('combobox')).toHaveFocus()
    await user.tab({ shift: true })
    expect(screen.getByRole('combobox')).toHaveFocus()
  })

  it('restores focus to the trigger on close', async () => {
    const user = userEvent.setup()
    function Harness() {
      const [open, setOpen] = useState(false)
      return (
        <div>
          <button
            type="button"
            onClick={() => {
              setOpen(true)
            }}
          >
            Open palette
          </button>
          {open ? (
            <CommandPalette
              onClose={() => {
                setOpen(false)
              }}
              commands={makeCommands(() => {})}
              labels={labels}
            />
          ) : null}
        </div>
      )
    }
    render(<Harness />)
    const trigger = screen.getByRole('button', { name: 'Open palette' })

    await user.click(trigger)
    expect(screen.getByRole('combobox')).toHaveFocus()

    await user.keyboard('{Escape}')
    expect(screen.queryByRole('combobox')).not.toBeInTheDocument()
    expect(trigger).toHaveFocus()
  })
})
