import { useTranslation } from '@/shared/i18n'
import styles from './dashboard.module.css'

/**
 * KPI strip. Only "active apps" has a real source today; updates / members /
 * audit are Origin- or aggregation-fed and shown as an honest "—" (Phase B),
 * never a fabricated number.
 */
export function KpiStrip({ installedCount }: { installedCount: number }) {
  const { t } = useTranslation()
  const unavailable = t('suite.home.kpi.unavailable')

  return (
    <div className={styles['kpiStrip']}>
      <div className={styles['kpiCell']}>
        <div className={styles['kpiNum']}>{installedCount}</div>
        <div className={styles['kpiLabel']}>{t('suite.home.kpi.installed')}</div>
      </div>
      <div className={styles['kpiCell']}>
        <div className={styles['kpiNumMuted']} title={unavailable}>
          —
        </div>
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
