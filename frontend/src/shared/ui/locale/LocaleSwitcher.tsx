import { LOCALES, resolveLocale, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'

/** Language selector backed by the i18n context; persists via the i18n provider. */
export function LocaleSwitcher() {
  const { t, locale, setLocale } = useTranslation()

  return (
    <label>
      {t('suite.locale.label')}
      <select
        value={locale}
        aria-label={t('suite.locale.select')}
        onChange={(event) => {
          setLocale(resolveLocale(event.target.value))
        }}
      >
        {SUPPORTED_LOCALE_IDS.map((id) => (
          <option key={id} value={id}>
            {LOCALES[id].label}
          </option>
        ))}
      </select>
    </label>
  )
}
