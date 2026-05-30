import type { SupportedLocale } from '../locales'
import type { MessageCatalog } from './en'
import { de } from './de'
import { en } from './en'
import { fr } from './fr'
import { ja } from './ja'
import { ptBR } from './pt-BR'
import { zhHans } from './zh-Hans'

const MESSAGES: Record<SupportedLocale, Partial<MessageCatalog>> = {
  en,
  ja,
  fr,
  'zh-Hans': zhHans,
  'pt-BR': ptBR,
  de,
}

export function getMessages(locale: SupportedLocale): Partial<MessageCatalog> {
  return MESSAGES[locale]
}
