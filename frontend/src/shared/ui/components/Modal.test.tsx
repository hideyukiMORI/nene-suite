import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'
import { describe, expect, it, vi } from 'vitest'
import { Modal } from './Modal'

function ModalHarness() {
  const [open, setOpen] = useState(false)
  return (
    <div>
      <button
        type="button"
        onClick={() => {
          setOpen(true)
        }}
      >
        Open modal
      </button>
      {open ? (
        <Modal
          onClose={() => {
            setOpen(false)
          }}
          ariaLabel="Example dialog"
          closeLabel="Close"
        >
          <button type="button">First action</button>
          <button type="button">Second action</button>
        </Modal>
      ) : null}
    </div>
  )
}

describe('Modal', () => {
  it('moves focus into the panel on open', async () => {
    const user = userEvent.setup()
    render(<ModalHarness />)

    await user.click(screen.getByRole('button', { name: 'Open modal' }))

    expect(screen.getByRole('dialog', { name: 'Example dialog' })).toBeInTheDocument()
    const panelButtons = screen.getAllByRole('button', { name: /Close|action/ })
    expect(panelButtons).toContain(document.activeElement)
  })

  it('traps Tab inside the panel', async () => {
    const user = userEvent.setup()
    render(<ModalHarness />)
    await user.click(screen.getByRole('button', { name: 'Open modal' }))

    // Close → First action → Second action → back to Close (never the opener).
    await user.tab()
    expect(document.activeElement).toHaveTextContent('First action')
    await user.tab()
    expect(document.activeElement).toHaveTextContent('Second action')
    await user.tab()
    expect(document.activeElement).toHaveAccessibleName('Close')

    await user.tab({ shift: true })
    expect(document.activeElement).toHaveTextContent('Second action')
  })

  it('restores focus to the trigger on close', async () => {
    const user = userEvent.setup()
    render(<ModalHarness />)
    const trigger = screen.getByRole('button', { name: 'Open modal' })

    await user.click(trigger)
    await user.keyboard('{Escape}')

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    expect(trigger).toHaveFocus()
  })

  it('closes on Escape and on the close button', async () => {
    const user = userEvent.setup()
    const onClose = vi.fn()
    render(
      <Modal onClose={onClose} ariaLabel="Example dialog" closeLabel="Close">
        <p>Body</p>
      </Modal>,
    )

    await user.keyboard('{Escape}')
    const dialog = screen.getByRole('dialog', { name: 'Example dialog' })
    await user.click(within(dialog).getByRole('button', { name: 'Close' }))

    expect(onClose).toHaveBeenCalledTimes(2)
  })
})
