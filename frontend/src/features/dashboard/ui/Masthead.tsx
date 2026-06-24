import { useState } from 'react'
import { Link } from 'react-router-dom'
import { authStore } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { GREET_SWATCHES, useGreetColor } from '../hooks/use-greet-color'
import styles from './dashboard.module.css'

/** Masthead: eyebrow + greeting (with a cosmetic name-color picker) + CTA. */
export function Masthead() {
  const { t } = useTranslation()
  const { color, setColor } = useGreetColor()
  const [pickerOpen, setPickerOpen] = useState(false)

  const session = authStore.getSession()
  const email = session?.operator.email ?? ''
  const name = session?.operator.displayName ?? (email.split('@')[0] || 'superadmin')
  const nameColor = color === '' ? 'var(--accent)' : color

  return (
    <div className={styles['masthead']}>
      <div>
        <div className={styles['eyebrow']}>
          {t('suite.home.eyebrow')} · {t('suite.org.indicator.superadmin')}
        </div>
        <h1 className={styles['greeting']}>
          {t('suite.home.greeting')}
          <span className={styles['name']} style={{ color: nameColor }}>
            {name}
          </span>
          <span className={styles['pickerWrap']}>
            <button
              type="button"
              className={styles['pickerBtn']}
              title={t('suite.home.greetColor')}
              aria-label={t('suite.home.greetColor')}
              aria-expanded={pickerOpen}
              onClick={() => {
                setPickerOpen((value) => !value)
              }}
            >
              <Icon name="palette" size={16} />
            </button>
            {pickerOpen ? (
              <>
                <button
                  type="button"
                  className={styles['scrim']}
                  aria-label={t('common.actions.close')}
                  onClick={() => {
                    setPickerOpen(false)
                  }}
                />
                <div className={styles['pickerPop']}>
                  <div className={styles['pickerTitle']}>{t('suite.home.greetColor')}</div>
                  <div className={styles['swatchGrid']}>
                    {GREET_SWATCHES.map((swatch) => (
                      <button
                        key={swatch}
                        type="button"
                        className={styles['swatch']}
                        data-active={color === swatch}
                        style={{ background: swatch }}
                        aria-label={swatch}
                        onClick={() => {
                          setColor(swatch)
                        }}
                      />
                    ))}
                  </div>
                  <label className={styles['customRow']}>
                    <input
                      type="color"
                      className={styles['customInput']}
                      value={color === '' ? '#0e7c86' : color}
                      onInput={(event) => {
                        setColor(event.currentTarget.value)
                      }}
                    />
                    {t('suite.home.greetColor.custom')}
                  </label>
                </div>
              </>
            ) : null}
          </span>
        </h1>
        <p className={styles['subtitle']}>{t('suite.home.subtitle')}</p>
      </div>
      <Link to="/install" className={styles['getApps']}>
        <Icon name="apps" size={19} />
        {t('suite.nav.getApps')}
      </Link>
    </div>
  )
}
