import { resolveLocale, useTranslation } from '@/shared/i18n'
import styles from './LocaleToggle.module.css'

/** Compact JA/EN toggle (we maintain ja+en; other locales fall back to en). */
export function LocaleToggle() {
  const { locale, setLocale, t } = useTranslation()
  const next = locale === 'ja' ? 'en' : 'ja'
  return (
    <button
      type="button"
      className={styles['toggle']}
      title={t('suite.locale.select')}
      aria-label={t('suite.locale.select')}
      onClick={() => {
        setLocale(resolveLocale(next))
      }}
    >
      {locale.slice(0, 2).toUpperCase()}
    </button>
  )
}
