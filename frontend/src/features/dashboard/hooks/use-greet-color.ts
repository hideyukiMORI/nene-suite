import { useCallback, useState } from 'react'

const STORAGE_KEY = 'nene-suite-greet-color'

/** Preset name-color swatches for the masthead greeting (cosmetic, client-only). */
export const GREET_SWATCHES = [
  '#0e7c86',
  '#2563eb',
  '#7c3aed',
  '#db2777',
  '#dc2626',
  '#ea580c',
  '#16a34a',
  '#4b5563',
] as const

interface UseGreetColor {
  /** Empty string means "use the brand accent". */
  color: string
  setColor: (color: string) => void
}

/** The chosen greeting-name color, persisted locally. Purely cosmetic. */
export function useGreetColor(): UseGreetColor {
  const [color, setColorState] = useState<string>(() => {
    try {
      return localStorage.getItem(STORAGE_KEY) ?? ''
    } catch {
      return ''
    }
  })

  const setColor = useCallback((next: string) => {
    try {
      localStorage.setItem(STORAGE_KEY, next)
    } catch {
      // ignore persistence failure
    }
    setColorState(next)
  }, [])

  return { color, setColor }
}
