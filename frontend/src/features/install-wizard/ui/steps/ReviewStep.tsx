import type { InstallSession } from '@/entities/install-session'
import { useTranslation } from '@/shared/i18n'
import styles from '../install-wizard.module.css'

interface ReviewStepProps {
  session: InstallSession | undefined
  isPending: boolean
  onComplete: () => void
}

export function ReviewStep({ session, isPending, onComplete }: ReviewStepProps) {
  const { t } = useTranslation()
  const selectedApps = session?.selectedApps ?? []

  return (
    <div>
      <h3 className={styles['stepTitle']}>{t('suite.install.review.title')}</h3>
      <p className={styles['stepDesc']}>{t('suite.install.review.description')}</p>

      <ul className={styles['reviewList']}>
        {selectedApps.map((id) => (
          <li key={id} className={styles['reviewChip']}>
            {id}
          </li>
        ))}
      </ul>

      <div className={styles['summaryBox']}>{t('suite.install.review.preCompleteSummary')}</div>

      <div className={styles['actions']}>
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
