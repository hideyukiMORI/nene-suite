import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { LOCALE_STORAGE_KEY } from '@/shared/i18n'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { LocaleToggle } from './LocaleToggle'

describe('LocaleToggle', () => {
  it('toggles en → ja → en', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LocaleToggle />)
    const toggle = screen.getByRole('button')

    expect(toggle).toHaveTextContent('EN')

    await user.click(toggle)
    expect(toggle).toHaveTextContent('JA')
    expect(localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('ja')

    await user.click(toggle)
    expect(toggle).toHaveTextContent('EN')
    expect(localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('en')
  })

  it('clamps a stub locale to en — never implicitly to ja', async () => {
    const user = userEvent.setup()
    localStorage.setItem(LOCALE_STORAGE_KEY, 'fr')
    renderWithProviders(<LocaleToggle />)
    const toggle = screen.getByRole('button')

    // shows the current locale, but the first press clamps into the pair via en
    expect(toggle).toHaveTextContent('FR')

    await user.click(toggle)
    expect(localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('en')
    expect(toggle).toHaveTextContent('EN')
  })

  it('clamps zh-Hans to en as well', async () => {
    const user = userEvent.setup()
    localStorage.setItem(LOCALE_STORAGE_KEY, 'zh-Hans')
    renderWithProviders(<LocaleToggle />)

    await user.click(screen.getByRole('button'))

    expect(localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('en')
  })
})
