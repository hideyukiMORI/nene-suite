import type { SupportedLocale } from './locales'

export const LOCALE_FONT_FAMILY_VAR = '--font-sans'

export const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  en: 'ui-sans-serif, system-ui, sans-serif',
  ja: '"Hiragino Sans", "Yu Gothic UI", "Noto Sans JP", sans-serif',
  fr: 'ui-sans-serif, system-ui, sans-serif',
  'zh-Hans': '"PingFang SC", "Microsoft YaHei", "Noto Sans SC", sans-serif',
  'pt-BR': 'ui-sans-serif, system-ui, sans-serif',
  de: 'ui-sans-serif, system-ui, sans-serif',
}

export function getLocaleFontStack(locale: SupportedLocale): string {
  return LOCALE_FONT_STACKS[locale]
}

export function applyLocaleFontFamily(
  locale: SupportedLocale,
  root: HTMLElement = document.documentElement,
): void {
  root.style.setProperty(LOCALE_FONT_FAMILY_VAR, getLocaleFontStack(locale))
}
