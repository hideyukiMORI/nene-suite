import { useTranslation } from '@/shared/i18n'
import styles from './LocaleToggle.module.css'

/**
 * Compact header shortcut for the maintained locale pair (en + ja) only.
 * Clamp rule (docs/development/i18n.md): ja → en, en → ja, and any stub
 * locale (fr / zh-Hans / pt-BR / de) clamps to en — the fallback language the
 * user is already reading — never implicitly to ja. The full six-locale
 * picker is the LocaleSwitcher in Settings → General → Language.
 */
export function LocaleToggle() {
  const { locale, setLocale, t } = useTranslation()
  const next = locale === 'ja' ? 'en' : locale === 'en' ? 'ja' : 'en'
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
