import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { InfoHint } from './InfoHint'

describe('InfoHint', () => {
  it('shows and hides the note on click', async () => {
    const user = userEvent.setup()
    renderWithProviders(<InfoHint text="やさしい説明" label="これは何？" />)

    expect(screen.queryByText('やさしい説明')).not.toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'これは何？' }))
    expect(screen.getByText('やさしい説明')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'これは何？' }))
    expect(screen.queryByText('やさしい説明')).not.toBeInTheDocument()
  })

  it('closes the note on Escape', async () => {
    const user = userEvent.setup()
    renderWithProviders(<InfoHint text="やさしい説明" label="これは何？" />)

    await user.click(screen.getByRole('button', { name: 'これは何？' }))
    expect(screen.getByText('やさしい説明')).toBeInTheDocument()
    await user.keyboard('{Escape}')
    expect(screen.queryByText('やさしい説明')).not.toBeInTheDocument()
  })
})
