import type { InstallSession } from '@/entities/install-session'
import { useTranslation } from '@/shared/i18n'

interface ReviewStepProps {
  session: InstallSession | undefined
  isPending: boolean
  onComplete: () => void
}

export function ReviewStep({ session, isPending, onComplete }: ReviewStepProps) {
  const { t } = useTranslation()
  const selectedApps = session?.selectedApps ?? []

  return (
    <section>
      <h3>{t('suite.install.review.title')}</h3>
      <p>{t('suite.install.review.description')}</p>

      <h4>{t('suite.install.review.selectedApps')}</h4>
      <ul>
        {selectedApps.map((id) => (
          <li key={id}>{id}</li>
        ))}
      </ul>

      <p>{t('suite.install.review.preCompleteSummary')}</p>

      <button type="button" disabled={isPending || session === undefined} onClick={onComplete}>
        {t('common.actions.finish')}
      </button>
    </section>
  )
}
