import { useEffect, type ReactNode } from 'react'
import { Icon } from '../Icon'
import styles from './Modal.module.css'

interface ModalProps {
  onClose: () => void
  ariaLabel: string
  closeLabel: string
  children: ReactNode
}

/**
 * Centered modal (DESIGN-SYSTEM.md §5.5): scrim + popIn panel. Mounted only
 * while open (caller renders conditionally). Closes on Escape or scrim/close.
 */
export function Modal({ onClose, ariaLabel, closeLabel, children }: ModalProps) {
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
      <div className={styles['panel']} role="dialog" aria-modal aria-label={ariaLabel}>
        <button type="button" className={styles['close']} aria-label={closeLabel} onClick={onClose}>
          <Icon name="close" size={20} />
        </button>
        {children}
      </div>
    </div>
  )
}
