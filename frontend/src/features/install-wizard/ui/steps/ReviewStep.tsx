import type { CatalogApp } from '@/entities/catalog-app'
import type { InstallSession } from '@/entities/install-session'
import { useTranslation } from '@/shared/i18n'
import styles from '../install-wizard.module.css'

interface ReviewStepProps {
  session: InstallSession | undefined
  apps: CatalogApp[]
  isPending: boolean
  onComplete: () => void
  onBack: () => void
}

export function ReviewStep({ session, apps, isPending, onComplete, onBack }: ReviewStepProps) {
  const { t } = useTranslation()
  const selectedApps = session?.selectedApps ?? []
  const targets = session?.databaseTargets ?? []

  const nameOf = (id: string): string => apps.find((app) => app.id === id)?.name ?? id

  const databaseSummaryOf = (id: string): string => {
    const target = targets.find((entry) => entry.catalogId === id)
    if (target === undefined || target.mode !== 'adopt') {
      return t('suite.install.database.summary.provision')
    }
    const parts = [t('suite.install.database.summary.adopt')]
    if (target.name !== null && target.name !== '') parts.push(target.name)
    if (target.server !== null && target.server !== '') parts.push(`@ ${target.server}`)
    return parts.join(' · ')
  }

  // Clear writes to Invoice over HTTP when both are installed (catalog dependency).
  const clearToInvoice =
    selectedApps.includes('nene-clear') && selectedApps.includes('nene-invoice')

  return (
    <div>
      <h3 className={styles['stepTitle']}>{t('suite.install.review.title')}</h3>
      <p className={styles['stepDesc']}>{t('suite.install.review.description')}</p>

      {session?.orgDisplayName !== null && session?.orgDisplayName !== undefined ? (
        <div className={styles['reviewRow']}>
          <span className={styles['reviewRowLabel']}>{t('suite.install.review.orgName')}</span>
          <span className={styles['reviewRowValue']}>{session.orgDisplayName}</span>
        </div>
      ) : null}

      <p className={styles['reviewSubhead']}>{t('suite.install.review.selectedApps')}</p>
      {selectedApps.length === 0 ? (
        <p className={styles['stepDesc']}>{t('suite.install.apps.empty')}</p>
      ) : (
        <ul className={styles['reviewAppList']}>
          {selectedApps.map((id) => (
            <li key={id} className={styles['reviewAppRow']}>
              <span className={styles['reviewAppName']}>{nameOf(id)}</span>
              <span className={styles['reviewAppDb']}>{databaseSummaryOf(id)}</span>
            </li>
          ))}
        </ul>
      )}

      <p className={styles['reviewSubhead']}>{t('suite.install.review.integrations.title')}</p>
      <p className={styles['reviewIntegration']}>
        {clearToInvoice
          ? t('suite.install.review.integrations.clearToInvoice')
          : t('suite.install.review.integrations.none')}
      </p>

      <div className={styles['summaryBox']}>{t('suite.install.review.preCompleteSummary')}</div>

      <div className={styles['actions']}>
        <button type="button" className={styles['backBtn']} disabled={isPending} onClick={onBack}>
          {t('common.actions.back')}
        </button>
        <button
          type="button"
          className={styles['primaryBtn']}
          disabled={isPending || session === undefined}
          onClick={onComplete}
        >
          {t('common.actions.finish')}
        </button>
      </div>
    </div>
  )
}
