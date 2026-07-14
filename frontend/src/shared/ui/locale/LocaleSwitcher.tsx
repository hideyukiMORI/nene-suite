import { LOCALES, resolveLocale, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'

interface LocaleSwitcherProps {
  /** Class for the wrapping <label> (layout is owned by the mount point). */
  readonly className?: string
  /** Class for the label text. */
  readonly labelClassName?: string
  /** Class for the <select>. */
  readonly selectClassName?: string
}

/**
 * Locale selector backed by the i18n context; persists via the i18n
 * provider. Currently offers the two maintained locales (en / ja) — same
 * pair as the header LocaleToggle (docs/development/i18n.md).
 */
export function LocaleSwitcher({
  className,
  labelClassName,
  selectClassName,
}: LocaleSwitcherProps = {}) {
  const { t, locale, setLocale } = useTranslation()

  return (
    <label className={className}>
      <span className={labelClassName}>{t('suite.locale.label')}</span>
      <select
        className={selectClassName}
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
