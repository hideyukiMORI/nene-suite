import type { SupportedLocale } from './locales'

export const LOCALE_FONT_FAMILY_VAR = '--font-x-sans'

// Design typography (DESIGN-SYSTEM.md §2): Noto Sans JP leads the body/UI stack
// so the apex shell is visually consistent ja↔en; each locale keeps its native
// system fallbacks for offline / font-load failure.
export const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  en: '"Noto Sans JP", ui-sans-serif, system-ui, sans-serif',
  ja: '"Noto Sans JP", "Hiragino Sans", "Yu Gothic UI", sans-serif',
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
