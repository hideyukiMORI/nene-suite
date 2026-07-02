import { useEffect, useRef, type ReactNode } from 'react'
import { Icon } from '../Icon'
import styles from './Drawer.module.css'
import { useFocusTrap } from './use-focus-trap'

interface DrawerProps {
  onClose: () => void
  /** Accessible name for the dialog. */
  ariaLabel: string
  /** Accessible label for the close affordances. */
  closeLabel: string
  children: ReactNode
}

/**
 * Right-side drawer (DESIGN-SYSTEM.md §5.5): scrim + 440px panel with drwIn.
 * Mounted only while open (caller conditionally renders it), so it carries no
 * open state. Closes on Escape or scrim/close-button click. Focus is trapped
 * inside the panel and restored to the trigger on close.
 */
export function Drawer({ onClose, ariaLabel, closeLabel, children }: DrawerProps) {
  const panelRef = useRef<HTMLElement>(null)
  useFocusTrap(panelRef)

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKeyDown)
    return () => {
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [onClose])

  return (
    <div className={styles['overlay']}>
      <button type="button" className={styles['scrim']} aria-label={closeLabel} onClick={onClose} />
      <aside
        ref={panelRef}
        className={styles['panel']}
        role="dialog"
        aria-modal
        aria-label={ariaLabel}
      >
        <button type="button" className={styles['close']} aria-label={closeLabel} onClick={onClose}>
          <Icon name="close" size={20} />
        </button>
        {children}
      </aside>
    </div>
  )
}
