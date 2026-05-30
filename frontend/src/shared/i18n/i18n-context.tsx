import { useCallback, useEffect, useState, type ReactNode } from 'react'
import { I18nContext } from './i18n-context-ref'
import { applyLocaleFontFamily } from './locale-fonts'
import { LOCALE_STORAGE_KEY, LOCALES, resolveLocale, type SupportedLocale } from './locales'
import { getMessages } from './messages'
import { translate, type MessageKey, type MessageParams } from './translate'

function detectLocale(): SupportedLocale {
  try {
    const stored = localStorage.getItem(LOCALE_STORAGE_KEY)
    if (stored !== null) return resolveLocale(stored)
  } catch {
    // localStorage blocked
  }
  return resolveLocale(navigator.language)
}

function applyLocaleToDocument(locale: SupportedLocale): void {
  document.documentElement.lang = locale
  document.documentElement.dir = LOCALES[locale].dir
  applyLocaleFontFamily(locale)
}

export function I18nProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<SupportedLocale>(detectLocale)

  const setLocale = useCallback((next: SupportedLocale) => {
    try {
      localStorage.setItem(LOCALE_STORAGE_KEY, next)
    } catch {
      // ignore
    }
    setLocaleState(next)
    applyLocaleToDocument(next)
  }, [])

  useEffect(() => {
    applyLocaleToDocument(locale)
  }, [locale])

  const messages = getMessages(locale)

  const t = useCallback(
    (key: MessageKey, params?: MessageParams): string => translate(messages, key, params),
    [messages],
  )

  return <I18nContext.Provider value={{ locale, setLocale, t }}>{children}</I18nContext.Provider>
}
