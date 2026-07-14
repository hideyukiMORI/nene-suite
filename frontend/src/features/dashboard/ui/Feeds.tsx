import type { ReactNode } from 'react'
import type { InstalledApp } from '@/entities/installed-app'
import {
  toOriginAd,
  toOriginAnnouncement,
  useOriginAnnouncements,
  useOriginHouseAds,
} from '@/entities/origin-feed'
import { useOriginUpdates } from '@/entities/origin-update'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './dashboard.module.css'

/**
 * Updates + announcements feeds + an optional house-ad slot (Origin-fed, ADR 0017). Each surface is
 * wired to its verified `/api/v1/origin/*` endpoint; when Origin is unconfigured (`available:false`)
 * / loading / errored, the updates and announcements panels keep the honest Phase-B placeholder —
 * never fabricated data. The house-ad slot renders only when verified ads exist (free cohort).
 */
export function Feeds({ apps }: { apps: InstalledApp[] }) {
  const { t, locale } = useTranslation()
  const updates = useOriginUpdates()
  const announcementsQuery = useOriginAnnouncements(locale)
  const houseAdsQuery = useOriginHouseAds(locale)
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

  const announcements =
    announcementsQuery.data?.available === true
      ? announcementsQuery.data.feeds
          .filter((feed) => feed.available)
          .flatMap((feed) =>
            feed.items.map((item) => ({ product: feed.product, ...toOriginAnnouncement(item) })),
          )
      : []

  let announcementsBody: ReactNode
  if (
    announcementsQuery.isLoading ||
    announcementsQuery.isError ||
    announcementsQuery.data === undefined ||
    !announcementsQuery.data.available
  ) {
    announcementsBody = (
      <p className={styles['feedPlaceholder']}>{t('suite.home.feeds.placeholder')}</p>
    )
  } else if (announcements.length === 0) {
    announcementsBody = (
      <p className={styles['feedPlaceholder']}>{t('suite.home.announcements.empty')}</p>
    )
  } else {
    announcementsBody = (
      <ul className={styles['feedList']}>
        {announcements.map((announcement) => (
          <li key={`${announcement.product}-${announcement.id}`} className={styles['announcement']}>
            <div className={styles['announcementHead']}>
              <span className={styles['severityBadge']} data-severity={announcement.severity}>
                {announcement.severity}
              </span>
              <span className={styles['announcementTitle']}>{announcement.title}</span>
            </div>
            {announcement.bodyMd !== '' ? (
              <p className={styles['announcementBody']}>{announcement.bodyMd}</p>
            ) : null}
          </li>
        ))}
      </ul>
    )
  }

  const ads =
    houseAdsQuery.data?.available === true
      ? houseAdsQuery.data.feeds
          .filter((feed) => feed.available)
          .flatMap((feed) =>
            feed.items.map((item) => ({ product: feed.product, ...toOriginAd(item) })),
          )
      : []

  return (
    <>
      <div className={styles['feeds']}>
        <div className={styles['feedPanel']}>
          <div className={styles['feedHead']}>
            <Icon name="download_for_offline" size={21} color="var(--color-warn)" />
            <h3>{t('suite.home.updates.title')}</h3>
            <span className={styles['originTag']}>Origin</span>
          </div>
          {updatesBody}
        </div>
        <div className={styles['feedPanel']}>
          <div className={styles['feedHead']}>
            <Icon name="campaign" size={21} color="var(--color-accent)" />
            <h3>{t('suite.home.announcements.title')}</h3>
            <span className={styles['originTag']}>Origin</span>
          </div>
          {announcementsBody}
        </div>
      </div>
      {ads.length > 0 ? (
        <aside className={styles['houseAds']} aria-label={t('suite.home.houseAds.title')}>
          <span className={styles['houseAdsTag']}>{t('suite.home.houseAds.title')}</span>
          {ads.map((ad) =>
            ad.linkUrl !== null ? (
              <a
                key={`${ad.product}-${ad.id}`}
                className={styles['houseAd']}
                href={ad.linkUrl}
                target="_blank"
                rel="noreferrer noopener"
              >
                <span className={styles['houseAdTitle']}>{ad.title}</span>
                {ad.bodyMd !== '' ? (
                  <span className={styles['houseAdBody']}>{ad.bodyMd}</span>
                ) : null}
              </a>
            ) : (
              <div key={`${ad.product}-${ad.id}`} className={styles['houseAd']}>
                <span className={styles['houseAdTitle']}>{ad.title}</span>
                {ad.bodyMd !== '' ? (
                  <span className={styles['houseAdBody']}>{ad.bodyMd}</span>
                ) : null}
              </div>
            ),
          )}
        </aside>
      ) : null}
    </>
  )
}
