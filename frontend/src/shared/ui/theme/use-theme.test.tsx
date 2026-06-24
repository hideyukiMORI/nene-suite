import { render, renderHook, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it } from 'vitest'
import { ThemeToggle } from '../ThemeToggle'
import { ThemeProvider } from './theme-context'
import { THEME_STORAGE_KEY } from './theme-context-ref'
import { useTheme } from './use-theme'

beforeEach(() => {
  localStorage.clear()
  delete document.documentElement.dataset['theme']
})

describe('ThemeProvider + useTheme', () => {
  it('applies a default theme to the document on mount', () => {
    renderHook(() => useTheme(), { wrapper: ThemeProvider })
    expect(document.documentElement.dataset['theme']).toBe('light')
  })

  it('hydrates the persisted theme from localStorage', () => {
    localStorage.setItem(THEME_STORAGE_KEY, 'dark')
    const { result } = renderHook(() => useTheme(), { wrapper: ThemeProvider })
    expect(result.current.theme).toBe('dark')
    expect(document.documentElement.dataset['theme']).toBe('dark')
  })

  it('throws when used outside the provider', () => {
    expect(() => renderHook(() => useTheme())).toThrow(/ThemeProvider/)
  })
})

describe('ThemeToggle', () => {
  it('toggles the theme and persists the choice', async () => {
    const user = userEvent.setup()
    render(
      <ThemeProvider>
        <ThemeToggle label="Toggle theme" />
      </ThemeProvider>,
    )

    expect(document.documentElement.dataset['theme']).toBe('light')

    await user.click(screen.getByRole('button', { name: 'Toggle theme' }))

    expect(document.documentElement.dataset['theme']).toBe('dark')
    expect(localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark')
  })
})
