import { useEffect, useRef, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Icon, useFocusTrap } from '@/shared/ui'
import styles from './NotificationsMenu.module.css'

/**
 * Notifications bell. The aggregated update / announcement / key-expiry signals
 * are Origin-fed (Phase B), so this shows an honest placeholder — no fabricated
 * notifications and no unread dot until there is a real feed. Opening moves
 * focus into the popover; Escape/Tab close it returning focus to the trigger.
 */
export function NotificationsMenu() {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  return (
    <div className={styles['root']}>
      <button
        type="button"
        className={styles['bell']}
        aria-label={t('suite.shell.notifications')}
        aria-haspopup="dialog"
        aria-expanded={open}
        onClick={() => {
          setOpen((value) => !value)
        }}
      >
        <Icon name="notifications" size={20} />
      </button>
      {open ? (
        <NotificationsPopover
          onClose={() => {
            setOpen(false)
          }}
        />
      ) : null}
    </div>
  )
}

/**
 * Mounted only while open so the focus trap moves focus into the panel on
 * open and restores it to the bell trigger on close.
 */
function NotificationsPopover({ onClose }: { onClose: () => void }) {
  const { t } = useTranslation()
  const panelRef = useRef<HTMLDivElement>(null)
  useFocusTrap(panelRef)

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent): void => {
      if (event.key !== 'Escape' && event.key !== 'Tab') return
      event.preventDefault()
      onClose()
    }
    window.addEventListener('keydown', onKeyDown)
    return () => {
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [onClose])

  return (
    <>
      <button
        type="button"
        className={styles['scrim']}
        aria-label={t('common.actions.close')}
        tabIndex={-1}
        onClick={onClose}
      />
      <div
        ref={panelRef}
        className={styles['menu']}
        role="dialog"
        aria-label={t('suite.shell.notifications')}
        tabIndex={-1}
      >
        <div className={styles['header']}>{t('suite.shell.notifications')}</div>
        <p className={styles['placeholder']}>{t('suite.shell.notifications.placeholder')}</p>
      </div>
    </>
  )
}
