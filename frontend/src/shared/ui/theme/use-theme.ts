import { useContext } from 'react'
import { ThemeContext, type ThemeContextValue } from './theme-context-ref'

export function useTheme(): ThemeContextValue {
  const ctx = useContext(ThemeContext)
  if (ctx === null) {
    throw new Error('useTheme must be called inside <ThemeProvider>')
  }
  return ctx
}
