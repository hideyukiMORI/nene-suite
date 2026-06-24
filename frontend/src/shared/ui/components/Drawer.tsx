import { useEffect, type ReactNode } from 'react'
import { Icon } from '../Icon'
import styles from './Drawer.module.css'

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
 * open state. Closes on Escape or scrim/close-button click.
 */
export function Drawer({ onClose, ariaLabel, closeLabel, children }: DrawerProps) {
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
      <aside className={styles['panel']} role="dialog" aria-modal aria-label={ariaLabel}>
        <button type="button" className={styles['close']} aria-label={closeLabel} onClick={onClose}>
          <Icon name="close" size={20} />
        </button>
        {children}
      </aside>
    </div>
  )
}
