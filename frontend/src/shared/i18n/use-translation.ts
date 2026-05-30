import { useContext } from 'react'
import { I18nContext, type I18nContextValue } from './i18n-context-ref'

export function useTranslation(): I18nContextValue {
  const ctx = useContext(I18nContext)
  if (ctx === null) {
    throw new Error('useTranslation must be called inside <I18nProvider>')
  }
  return ctx
}
