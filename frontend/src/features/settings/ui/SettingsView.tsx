import { useState } from 'react'
import { Link } from 'react-router-dom'
import { env } from '@/shared/config/env'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher } from '@/shared/ui'
import { DisclaimerModal } from './DisclaimerModal'
import styles from './settings.module.css'

/**
 * Settings. Edition is real (build config); the disclaimer modal is real and
 * binding (ADR 0003). Origin polling / update channel are Phase B placeholders;
 * federation keys links to the admin hub.
 */
export function SettingsView() {
  const { t } = useTranslation()
  const [showDisclaimer, setShowDisclaimer] = useState(false)
  const phaseB = t('suite.settings.phaseB')

  return (
    <div className={styles['root']}>
      <h1 className={styles['title']}>{t('suite.settings.title')}</h1>
      <p className={styles['subtitle']}>{t('suite.settings.subtitle')}</p>

      <section className={styles['section']}>
        <h2 className={styles['sectionTitle']}>{t('suite.settings.section.general')}</h2>
        <div className={styles['list']}>
          <div className={styles['row']}>
            <span className={styles['rowLabel']}>{t('suite.settings.edition')}</span>
            <span className={styles['mono']}>{env.edition.toUpperCase()}</span>
          </div>
          {/* Full six-locale picker — the header toggle only clamps within en+ja. */}
          <LocaleSwitcher
            className={styles['localeRow'] ?? ''}
            labelClassName={styles['rowLabel'] ?? ''}
            selectClassName={styles['localeSelect'] ?? ''}
          />
          <div className={styles['row']}>
            <span className={styles['rowLabel']}>{t('suite.settings.federationKeys')}</span>
            <Link className={styles['rowLink']} to="/admin/organizations?tab=keys">
              {t('suite.settings.federationKeys.manage')}
            </Link>
          </div>
          <div className={styles['row']}>
            <span className={styles['rowLabel']}>{t('suite.settings.originPolling')}</span>
            <span className={styles['rowMuted']} title={phaseB}>
              {phaseB}
            </span>
          </div>
          <div className={styles['row']}>
            <span className={styles['rowLabel']}>{t('suite.settings.updateChannel')}</span>
            <span className={styles['rowMuted']} title={phaseB}>
              {phaseB}
            </span>
          </div>
        </div>
      </section>

      <section className={styles['section']}>
        <h2 className={styles['sectionTitle']}>{t('suite.settings.section.about')}</h2>
        <div className={styles['list']}>
          <div className={styles['row']}>
            <span className={styles['rowLabel']}>
              {t('suite.settings.disclaimer')}
              <span className={styles['bindingHint']}>
                {t('suite.settings.disclaimer.binding')}
              </span>
            </span>
            <button
              type="button"
              className={styles['rowBtn']}
              onClick={() => {
                setShowDisclaimer(true)
              }}
            >
              {t('suite.settings.disclaimer.view')}
            </button>
          </div>
        </div>
      </section>

      {showDisclaimer ? (
        <DisclaimerModal
          onClose={() => {
            setShowDisclaimer(false)
          }}
        />
      ) : null}
    </div>
  )
}
