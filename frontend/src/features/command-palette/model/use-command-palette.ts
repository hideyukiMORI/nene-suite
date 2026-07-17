import { useCallback, useEffect, useState } from 'react'

interface UseCommandPalette {
  isOpen: boolean
  open: () => void
  close: () => void
}

/**
 * Owns command-palette open state and the global ⌘K / Ctrl-K shortcut. Esc-close
 * is handled inside the palette (where the input has focus).
 */
export function useCommandPalette(): UseCommandPalette {
  const [isOpen, setIsOpen] = useState(false)

  const open = useCallback(() => {
    setIsOpen(true)
  }, [])
  const close = useCallback(() => {
    setIsOpen(false)
  }, [])

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent): void => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        setIsOpen((current) => !current)
      }
    }
    window.addEventListener('keydown', onKeyDown)
    return () => {
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [])

  return { isOpen, open, close }
}
