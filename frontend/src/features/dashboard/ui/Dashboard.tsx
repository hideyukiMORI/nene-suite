import { useState } from 'react'
import { Link } from 'react-router-dom'
import { AppDetailDrawer } from '@/features/app-detail'
import { useTranslation } from '@/shared/i18n'
import { Icon, PlaceholderState } from '@/shared/ui'
import { useDashboard } from '../hooks/use-dashboard'
import styles from './dashboard.module.css'
import { Feeds } from './Feeds'
import { KpiStrip } from './KpiStrip'
import { Masthead } from './Masthead'
import { Pillars } from './Pillars'

const SKELETON_KEYS = ['s1', 's2', 's3']

/** Home dashboard: masthead + KPI + installed-app pillars + Origin feeds. */
export function Dashboard() {
  const { t } = useTranslation()
  const { apps, isLoading, isError, refetch } = useDashboard()
  const [detailId, setDetailId] = useState<string | null>(null)

  if (isLoading) {
    return (
      <div className={styles['root']}>
        <Masthead />
        <div className={styles['skeletonGrid']}>
          {SKELETON_KEYS.map((key) => (
            <div key={key} className={styles['skeletonCard']} />
          ))}
        </div>
      </div>
    )
  }

  if (isError) {
    return (
      <div className={styles['root']}>
        <Masthead />
        <div className={styles['errorPanel']} role="alert">
          <Icon name="error" size={40} fill color="var(--color-danger)" />
          <p>{t('common.state.error')}</p>
          <button type="button" className={styles['retryBtn']} onClick={refetch}>
            <Icon name="refresh" size={18} />
            {t('common.actions.retry')}
          </button>
        </div>
      </div>
    )
  }

  if (apps.length === 0) {
    return (
      <div className={styles['root']}>
        <Masthead />
        <PlaceholderState
          icon="apps"
          title={t('suite.home.firstRun.title')}
          description={t('suite.home.firstRun.subtitle')}
          action={<Link to="/install">{t('suite.nav.getApps')}</Link>}
        />
      </div>
    )
  }

  return (
    <div className={styles['root']}>
      <Masthead />
      <KpiStrip installedCount={apps.length} />
      <div className={styles['sectionTitle']}>
        <h2>{t('suite.home.appsTitle')}</h2>
        <span className={styles['sectionLine']} />
      </div>
      <Pillars apps={apps} onOpenDetail={setDetailId} />
      <Feeds apps={apps} />
      {detailId !== null ? (
        <AppDetailDrawer
          appId={detailId}
          onClose={() => {
            setDetailId(null)
          }}
        />
      ) : null}
    </div>
  )
}
