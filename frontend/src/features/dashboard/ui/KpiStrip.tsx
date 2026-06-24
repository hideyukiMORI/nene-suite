import { ACTIONABLE_UPDATE_STATUSES, useOriginUpdates } from '@/entities/origin-update'
import { useTranslation } from '@/shared/i18n'
import styles from './dashboard.module.css'

/**
 * KPI strip. "Active apps" has a real source; "Updates" is wired to the verified Origin signals
 * (count of apps with a confirmed actionable update). When Origin is unconfigured / loading /
 * errored it stays an honest "—" (Phase B), never a fabricated number. Members / audit remain "—"
 * (aggregation-fed, out of scope here).
 */
export function KpiStrip({ installedCount }: { installedCount: number }) {
  const { t } = useTranslation()
  const unavailable = t('suite.home.kpi.unavailable')
  const updates = useOriginUpdates()
  const updateCount =
    updates.data?.available === true
      ? updates.data.updates.filter((update) => ACTIONABLE_UPDATE_STATUSES.includes(update.status))
          .length
      : null

  return (
    <div className={styles['kpiStrip']}>
      <div className={styles['kpiCell']}>
        <div className={styles['kpiNum']}>{installedCount}</div>
        <div className={styles['kpiLabel']}>{t('suite.home.kpi.installed')}</div>
      </div>
      <div className={styles['kpiCell']}>
        {updateCount === null ? (
          <div className={styles['kpiNumMuted']} title={unavailable}>
            —
          </div>
        ) : (
          <div className={styles['kpiNum']}>{updateCount}</div>
        )}
        <div className={styles['kpiLabel']}>{t('suite.home.kpi.updates')}</div>
      </div>
      <div className={styles['kpiCell']}>
        <div className={styles['kpiNumMuted']} title={unavailable}>
          —
        </div>
        <div className={styles['kpiLabel']}>{t('suite.home.kpi.members')}</div>
      </div>
      <div className={styles['kpiCell']}>
        <div className={styles['kpiNumMuted']} title={unavailable}>
          —
        </div>
        <div className={styles['kpiLabel']}>{t('suite.home.kpi.audit')}</div>
      </div>
    </div>
  )
}
