import type { ReactNode } from 'react'
import type { InstalledApp } from '@/entities/installed-app'
import { useOriginUpdates } from '@/entities/origin-update'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './dashboard.module.css'

/**
 * Updates + announcements feeds (Origin-fed, ADR 0017). The updates panel is wired to the verified
 * `/api/v1/origin/updates`; when Origin is unconfigured (`available:false`) / loading / errored it
 * keeps the honest Phase-B placeholder — never fabricated data. Announcements stay a placeholder
 * until O5b. `status: unknown` (installed version not tracked yet) surfaces the latest available
 * version without claiming an update is needed.
 */
export function Feeds({ apps }: { apps: InstalledApp[] }) {
  const { t } = useTranslation()
  const updates = useOriginUpdates()
  const nameById = new Map(apps.map((app) => [app.catalogId, app.name]))

  let updatesBody: ReactNode
  if (updates.isLoading) {
    updatesBody = <p className={styles['feedPlaceholder']}>{t('suite.home.updates.checking')}</p>
  } else if (updates.isError || updates.data === undefined || !updates.data.available) {
    updatesBody = <p className={styles['feedPlaceholder']}>{t('suite.home.feeds.placeholder')}</p>
  } else if (updates.data.updates.length === 0) {
    updatesBody = <p className={styles['feedPlaceholder']}>{t('suite.home.updates.empty')}</p>
  } else {
    updatesBody = (
      <ul className={styles['feedList']}>
        {updates.data.updates.map((update) => (
          <li key={update.product} className={styles['feedRow']}>
            <span className={styles['feedRowName']}>
              {nameById.get(update.product) ?? update.product}
            </span>
            {update.latestVersion !== null ? (
              <span className={styles['feedRowVersion']}>
                {t('suite.home.updates.latest', { version: update.latestVersion })}
              </span>
            ) : null}
            <span className={styles['statusBadge']} data-status={update.status}>
              {t(`suite.home.updates.status.${update.status}`)}
            </span>
          </li>
        ))}
      </ul>
    )
  }

  return (
    <div className={styles['feeds']}>
      <div className={styles['feedPanel']}>
        <div className={styles['feedHead']}>
          <Icon name="download_for_offline" size={21} color="var(--warn)" />
          <h3>{t('suite.home.updates.title')}</h3>
          <span className={styles['originTag']}>Origin</span>
        </div>
        {updatesBody}
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
