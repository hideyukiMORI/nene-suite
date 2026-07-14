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

  it('falls back a removed stub-locale value (fr) to en on load — never to ja', () => {
    localStorage.setItem(LOCALE_STORAGE_KEY, 'fr')
    renderWithProviders(<LocaleToggle />)
    const toggle = screen.getByRole('button')

    // resolveLocale clamps the legacy value at detectLocale() time, before
    // the first render — no residual 'fr' state, and never an implicit 'ja'.
    expect(toggle).toHaveTextContent('EN')
  })

  it('falls back a removed stub-locale value (zh-Hans) to en on load as well', () => {
    localStorage.setItem(LOCALE_STORAGE_KEY, 'zh-Hans')
    renderWithProviders(<LocaleToggle />)

    expect(screen.getByRole('button')).toHaveTextContent('EN')
  })
})
