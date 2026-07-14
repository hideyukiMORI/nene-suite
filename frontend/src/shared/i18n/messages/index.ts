import type { SupportedLocale } from '../locales'
import type { MessageCatalog } from './en'
import { en } from './en'
import { ja } from './ja'

const MESSAGES: Record<SupportedLocale, Partial<MessageCatalog>> = {
  en,
  ja,
}

export function getMessages(locale: SupportedLocale): Partial<MessageCatalog> {
  return MESSAGES[locale]
}
