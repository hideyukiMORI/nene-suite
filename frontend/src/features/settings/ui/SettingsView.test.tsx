import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { SettingsView } from './SettingsView'

describe('SettingsView', () => {
  it('shows the edition and opens the binding disclaimer modal', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SettingsView />)

    // edition is real (build config, default OSS)
    expect(screen.getByText('OSS')).toBeInTheDocument()

    // disclaimer modal (ADR 0003) opens with the approved copy
    await user.click(screen.getByRole('button', { name: 'View disclaimer' }))
    expect(screen.getByRole('dialog', { name: 'Important notice' })).toBeInTheDocument()
  })

  it('mounts the locale switcher and switches between the two maintained locales', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SettingsView />)

    const select = screen.getByRole('combobox', { name: 'Select language' })
    expect(select).toBeInTheDocument()

    await user.selectOptions(select, 'ja')
    expect(select).toHaveValue('ja')
    expect(screen.getByText('言語')).toBeInTheDocument()

    await user.selectOptions(select, 'en')
    expect(select).toHaveValue('en')
    expect(screen.getByText('Language')).toBeInTheDocument()
  })
})
