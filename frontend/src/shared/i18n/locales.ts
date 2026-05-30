/**
 * i18n locale definitions — 6 locales matching NENE2 docs / nene-records.
 */

export type SupportedLocale = 'en' | 'ja' | 'fr' | 'zh-Hans' | 'pt-BR' | 'de'

export interface LocaleMeta {
  label: string
  dir: 'ltr' | 'rtl'
  nene2Id: string | null
}

export const LOCALES: Record<SupportedLocale, LocaleMeta> = {
  en: { label: 'English', dir: 'ltr', nene2Id: null },
  ja: { label: '日本語', dir: 'ltr', nene2Id: 'ja' },
  fr: { label: 'Français', dir: 'ltr', nene2Id: 'fr' },
  'zh-Hans': { label: '中文（简体）', dir: 'ltr', nene2Id: 'zh' },
  'pt-BR': { label: 'Português (Brasil)', dir: 'ltr', nene2Id: 'pt-br' },
  de: { label: 'Deutsch', dir: 'ltr', nene2Id: 'de' },
}

export const DEFAULT_LOCALE: SupportedLocale = 'en'

export const SUPPORTED_LOCALE_IDS = Object.keys(LOCALES) as SupportedLocale[]

export const LOCALE_STORAGE_KEY = 'nene-suite-locale'

export function resolveLocale(raw: string): SupportedLocale {
  if (SUPPORTED_LOCALE_IDS.includes(raw as SupportedLocale)) {
    return raw as SupportedLocale
  }
  const prefix = raw.split('-').slice(0, 2).join('-')
  if (SUPPORTED_LOCALE_IDS.includes(prefix as SupportedLocale)) {
    return prefix as SupportedLocale
  }
  const singlePrefix = raw.split('-')[0]
  if (SUPPORTED_LOCALE_IDS.includes(singlePrefix as SupportedLocale)) {
    return singlePrefix as SupportedLocale
  }
  return DEFAULT_LOCALE
}
