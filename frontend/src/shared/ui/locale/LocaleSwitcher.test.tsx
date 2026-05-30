import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { LocaleSwitcher } from './LocaleSwitcher'

describe('LocaleSwitcher', () => {
  it('switches the active locale and re-localizes labels', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LocaleSwitcher />)

    // Default English label.
    expect(screen.getByText('Language')).toBeInTheDocument()

    await user.selectOptions(screen.getByRole('combobox'), 'ja')

    // The label re-renders in Japanese (ja.ts).
    expect(screen.getByText('言語')).toBeInTheDocument()
  })
})
