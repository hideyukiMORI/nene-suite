import { useTranslation } from '@/shared/i18n'
import styles from './LocaleToggle.module.css'

/**
 * Compact header shortcut toggling the two supported locales (en ↔ ja).
 * `resolveLocale` (docs/development/i18n.md) already clamps any legacy or
 * unrecognized locale — including the removed fr / zh-Hans / pt-BR / de
 * stub catalogs — to `en` at load time, so by the time this component reads
 * `locale` it is always `'en'` or `'ja'`.
 */
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
        setLocale(next)
      }}
    >
      {locale.slice(0, 2).toUpperCase()}
    </button>
  )
}
