import { Fragment } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { useInstallWizard, type WizardStep } from '../hooks/use-install-wizard'
import styles from './install-wizard.module.css'
import { AppSelectionStep } from './steps/AppSelectionStep'
import { CompleteStep } from './steps/CompleteStep'
import { DatabaseStep } from './steps/DatabaseStep'
import { DisclaimerStep } from './steps/DisclaimerStep'
import { ReviewStep } from './steps/ReviewStep'

const STEP_ORDER: readonly WizardStep[] = ['apps', 'database', 'disclaimer', 'review', 'complete']

const STEP_LABEL: Record<WizardStep, MessageKey> = {
  apps: 'suite.install.wizard.step.apps',
  database: 'suite.install.wizard.step.database',
  disclaimer: 'suite.install.wizard.step.disclaimer',
  review: 'suite.install.wizard.step.review',
  complete: 'suite.install.wizard.step.complete',
}

export function InstallWizard() {
  const { t } = useTranslation()
  const wizard = useInstallWizard()

  if (wizard.sessionId === null) {
    return (
      <div className={styles['root']}>
        <div className={styles['card']}>
          <h2 className={styles['title']}>{t('suite.install.wizard.title')}</h2>
          <p className={styles['lead']}>{t('suite.install.apps.description')}</p>
          <div className={styles['startRow']}>
            <button
              type="button"
              className={styles['primaryBtn']}
              disabled={wizard.isStarting}
              onClick={wizard.start}
            >
              {t('suite.launcher.startInstall')}
            </button>
          </div>
        </div>
      </div>
    )
  }

  const currentIndex = STEP_ORDER.indexOf(wizard.step)

  return (
    <div className={styles['root']}>
      <div className={styles['card']}>
        <ol className={styles['stepper']}>
          {STEP_ORDER.map((value, index) => {
            const state =
              index < currentIndex ? 'done' : index === currentIndex ? 'current' : 'todo'
            return (
              <Fragment key={value}>
                {index > 0 ? <span className={styles['connector']} /> : null}
                <li className={styles['step']} data-state={state}>
                  <span className={styles['stepDot']}>
                    {state === 'done' ? <Icon name="check" size={15} /> : index + 1}
                  </span>
                  <span className={styles['stepLabel']}>{t(STEP_LABEL[value])}</span>
                </li>
              </Fragment>
            )
          })}
        </ol>

        {wizard.step === 'apps' ? (
          <AppSelectionStep
            apps={wizard.catalogApps}
            isPending={wizard.isMutating}
            onSubmit={wizard.selectApps}
          />
        ) : null}

        {wizard.step === 'database' ? (
          <DatabaseStep
            apps={(wizard.session?.selectedApps ?? []).map((id) => ({
              id,
              name: wizard.catalogApps.find((app) => app.id === id)?.name ?? id,
            }))}
            isPending={wizard.isMutating}
            onSubmit={wizard.setDatabaseTargets}
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
      </div>
    </div>
  )
}
