import { useEffect, type RefObject } from 'react'

const FOCUSABLE_SELECTOR = 'a[href], button, input, select, textarea, [tabindex]'

function focusableWithin(panel: HTMLElement): HTMLElement[] {
  return Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)).filter(
    (element) => !element.hasAttribute('disabled') && element.tabIndex >= 0,
  )
}

/**
 * Focus management for modal overlays (WAI-ARIA APG dialog pattern). On mount
 * moves focus into the panel (initialFocusRef, else first focusable, else the
 * panel itself), keeps Tab / Shift+Tab cycling inside the panel, and on
 * unmount returns focus to the element that was focused before the overlay
 * opened (the trigger). Callers mount the overlay only while open, so the
 * trap lives exactly as long as the overlay does.
 */
export function useFocusTrap(
  panelRef: RefObject<HTMLElement | null>,
  initialFocusRef?: RefObject<HTMLElement | null>,
): void {
  useEffect(() => {
    const panel = panelRef.current
    if (panel === null) return
    const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null

    const initial = initialFocusRef?.current ?? focusableWithin(panel)[0] ?? panel
    initial.focus()

    const onKeyDown = (event: KeyboardEvent): void => {
      if (event.key !== 'Tab') return
      const focusable = focusableWithin(panel)
      if (focusable.length === 0) {
        event.preventDefault()
        return
      }
      const first = focusable[0]
      const last = focusable[focusable.length - 1]
      const active = document.activeElement
      const outside = !(active instanceof HTMLElement) || !panel.contains(active)
      if (event.shiftKey) {
        if (outside || active === first) {
          event.preventDefault()
          last?.focus()
        }
      } else if (outside || active === last) {
        event.preventDefault()
        first?.focus()
      }
    }

    document.addEventListener('keydown', onKeyDown, true)
    return () => {
      document.removeEventListener('keydown', onKeyDown, true)
      if (opener !== null && opener.isConnected) opener.focus()
    }
  }, [panelRef, initialFocusRef])
}
