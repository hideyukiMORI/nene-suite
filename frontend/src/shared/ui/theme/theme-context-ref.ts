import { createContext } from 'react'

export type Theme = 'light' | 'dark'

export interface ThemeContextValue {
  theme: Theme
  setTheme: (theme: Theme) => void
  toggleTheme: () => void
}

export const ThemeContext = createContext<ThemeContextValue | null>(null)

/** Persistence key — suite-specific, mirrors the locale key convention. */
export const THEME_STORAGE_KEY = 'nene-suite-theme'
