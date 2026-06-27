import { useEffect, useId, useRef, useState, type ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '../Icon'
import styles from './InfoHint.module.css'

/**
 * Inline "?" annotation for a hard-to-understand field. Click to open a small
 * plain-language note; click away or press Escape to close. Keyboard- and
 * screen-reader-friendly (not a hover tooltip).
 */
export function InfoHint({
  text,
  label,
}: {
  readonly text: string
  readonly label?: string
}): ReactNode {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLSpanElement>(null)
  const panelId = useId()
  const ariaLabel = label ?? t('suite.help.infoHint')

  useEffect(() => {
    if (!open) return
    function onPointerDown(event: MouseEvent): void {
      const root = rootRef.current
      if (root !== null && event.target instanceof Node && !root.contains(event.target)) {
        setOpen(false)
      }
    }
    function onKeyDown(event: KeyboardEvent): void {
      if (event.key === 'Escape') setOpen(false)
    }
    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open])

  return (
    <span className={styles['root']} ref={rootRef}>
      <button
        type="button"
        className={styles['trigger']}
        aria-label={ariaLabel}
        aria-expanded={open}
        aria-controls={panelId}
        onClick={() => {
          setOpen((current) => !current)
        }}
      >
        <Icon name="help" size={15} />
      </button>
      {open ? (
        <span id={panelId} role="note" className={styles['panel']}>
          {text}
        </span>
      ) : null}
    </span>
  )
}
