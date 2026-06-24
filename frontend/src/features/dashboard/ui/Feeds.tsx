import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './dashboard.module.css'

/**
 * Updates + announcements feeds. These are Origin-fed (ADR 0017, Phase B), so
 * each panel shows an honest placeholder — no fabricated updates / announcements
 * / house ads until the Origin client lands.
 */
export function Feeds() {
  const { t } = useTranslation()

  return (
    <div className={styles['feeds']}>
      <div className={styles['feedPanel']}>
        <div className={styles['feedHead']}>
          <Icon name="download_for_offline" size={21} color="var(--warn)" />
          <h3>{t('suite.home.updates.title')}</h3>
          <span className={styles['originTag']}>Origin</span>
        </div>
        <p className={styles['feedPlaceholder']}>{t('suite.home.feeds.placeholder')}</p>
      </div>
      <div className={styles['feedPanel']}>
        <div className={styles['feedHead']}>
          <Icon name="campaign" size={21} color="var(--accent)" />
          <h3>{t('suite.home.announcements.title')}</h3>
        </div>
        <p className={styles['feedPlaceholder']}>{t('suite.home.feeds.placeholder')}</p>
      </div>
    </div>
  )
}
