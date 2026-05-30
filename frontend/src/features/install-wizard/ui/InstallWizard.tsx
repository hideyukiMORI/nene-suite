import { useTranslation } from '@/shared/i18n'
import { useInstallWizard } from '../hooks/use-install-wizard'
import { AppSelectionStep } from './steps/AppSelectionStep'
import { CompleteStep } from './steps/CompleteStep'
import { DisclaimerStep } from './steps/DisclaimerStep'
import { ReviewStep } from './steps/ReviewStep'

export function InstallWizard() {
  const { t } = useTranslation()
  const wizard = useInstallWizard()

  if (wizard.sessionId === null) {
    return (
      <section>
        <h2>{t('suite.install.wizard.title')}</h2>
        <button type="button" disabled={wizard.isStarting} onClick={wizard.start}>
          {t('suite.launcher.startInstall')}
        </button>
      </section>
    )
  }

  return (
    <section>
      <h2>{t('suite.install.wizard.title')}</h2>

      {wizard.step === 'apps' ? (
        <AppSelectionStep
          apps={wizard.catalogApps}
          isPending={wizard.isMutating}
          onSubmit={wizard.selectApps}
        />
      ) : null}

      {wizard.step === 'disclaimer' ? (
        <DisclaimerStep isPending={wizard.isMutating} onAccept={wizard.acceptDisclaimer} />
      ) : null}

      {wizard.step === 'review' ? (
        <ReviewStep
          session={wizard.session}
          isPending={wizard.isMutating}
          onComplete={wizard.complete}
        />
      ) : null}

      {wizard.step === 'complete' ? <CompleteStep /> : null}
    </section>
  )
}
