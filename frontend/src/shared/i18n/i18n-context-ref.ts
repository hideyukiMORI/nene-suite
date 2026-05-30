import { createContext } from 'react'
import type { SupportedLocale } from './locales'
import type { MessageKey, MessageParams } from './translate'

export interface I18nContextValue {
  locale: SupportedLocale
  setLocale: (locale: SupportedLocale) => void
  t: (key: MessageKey, params?: MessageParams) => string
}

export const I18nContext = createContext<I18nContextValue | null>(null)
