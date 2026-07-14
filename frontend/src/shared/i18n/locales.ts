/**
 * i18n locale definitions — en + ja only.
 *
 * fr / zh-Hans / pt-BR / de were removed 2026-07-14: each catalog held ~7
 * stub keys (English-fallback for everything else) and was never a real
 * translation, failing the frontend parity-100%-or-delete ship bar (frontend
 * standards doc 04). `resolveLocale` still falls back unknown/legacy tags —
 * including these four — to `en`, so a browser or `localStorage` still
 * carrying one of the old codes degrades safely instead of breaking.
 */

export type SupportedLocale = 'en' | 'ja'

export interface LocaleMeta {
  label: string
  dir: 'ltr' | 'rtl'
  nene2Id: string | null
}

export const LOCALES: Record<SupportedLocale, LocaleMeta> = {
  en: { label: 'English', dir: 'ltr', nene2Id: null },
  ja: { label: '日本語', dir: 'ltr', nene2Id: 'ja' },
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
  const singlePrefix = raw.split('-')[0] ?? ''
  if (SUPPORTED_LOCALE_IDS.includes(singlePrefix as SupportedLocale)) {
    return singlePrefix as SupportedLocale
  }
  return DEFAULT_LOCALE
}
