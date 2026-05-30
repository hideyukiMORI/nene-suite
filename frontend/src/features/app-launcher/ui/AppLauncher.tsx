import { Link } from 'react-router-dom'
import type { SsotRole } from '@/entities/installed-app'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { EmptyState, ErrorState, LoadingState } from '@/shared/ui'
import { useAppLauncher } from '../hooks/use-app-launcher'

const SSOT_LABEL_KEYS: Record<SsotRole, MessageKey | null> = {
  billing: 'suite.launcher.ssotBilling',
  reconciliation_evidence: 'suite.launcher.ssotEvidence',
  cms: 'suite.launcher.ssotCms',
  archive: 'suite.launcher.ssotArchive',
  none: null,
}

export function AppLauncher() {
  const { t } = useTranslation()
  const { apps, isLoading, isError } = useAppLauncher()

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }

  if (isError) {
    return <ErrorState label={t('common.state.error')} />
  }

  if (apps.length === 0) {
    return (
      <EmptyState
        title={t('suite.launcher.empty.title')}
        description={t('suite.launcher.empty.description')}
        action={<Link to="/install">{t('suite.launcher.startInstall')}</Link>}
      />
    )
  }

  return (
    <ul>
      {apps.map((app) => {
        const ssotKey = SSOT_LABEL_KEYS[app.ssotRole]

        return (
          <li key={app.catalogId}>
            <span>{app.name}</span>
            {ssotKey !== null ? <span>{t(ssotKey)}</span> : null}
            <a href={app.publicUrl}>{t('suite.launcher.openApp', { appName: app.name })}</a>
          </li>
        )
      })}
    </ul>
  )
}
