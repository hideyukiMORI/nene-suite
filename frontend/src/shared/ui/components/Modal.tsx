import { useEffect, useRef, type ReactNode } from 'react'
import { Icon } from '../Icon'
import styles from './Modal.module.css'
import { useFocusTrap } from './use-focus-trap'

interface ModalProps {
  onClose: () => void
  ariaLabel: string
  closeLabel: string
  children: ReactNode
}

/**
 * Centered modal (DESIGN-SYSTEM.md §5.5): scrim + popIn panel. Mounted only
 * while open (caller renders conditionally). Closes on Escape or scrim/close.
 * Focus is trapped inside the panel and restored to the trigger on close.
 */
export function Modal({ onClose, ariaLabel, closeLabel, children }: ModalProps) {
  const panelRef = useRef<HTMLDivElement>(null)
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
      <div
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
      </div>
    </div>
  )
}
