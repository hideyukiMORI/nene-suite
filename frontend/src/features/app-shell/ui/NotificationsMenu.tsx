import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './NotificationsMenu.module.css'

/**
 * Notifications bell. The aggregated update / announcement / key-expiry signals
 * are Origin-fed (Phase B), so this shows an honest placeholder — no fabricated
 * notifications and no unread dot until there is a real feed.
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
        <>
          <button
            type="button"
            className={styles['scrim']}
            aria-label={t('common.actions.close')}
            onClick={() => {
              setOpen(false)
            }}
          />
          <div className={styles['menu']} role="dialog" aria-label={t('suite.shell.notifications')}>
            <div className={styles['header']}>{t('suite.shell.notifications')}</div>
            <p className={styles['placeholder']}>{t('suite.shell.notifications.placeholder')}</p>
          </div>
        </>
      ) : null}
    </div>
  )
}
