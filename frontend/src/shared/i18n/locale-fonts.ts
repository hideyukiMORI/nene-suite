import type { SupportedLocale } from './locales'

export const LOCALE_FONT_FAMILY_VAR = '--font-sans'

// Design typography (DESIGN-SYSTEM.md §2): Noto Sans JP leads the body/UI stack
// so the apex shell is visually consistent ja↔en; each locale keeps its native
// system fallbacks for offline / font-load failure. Exception: zh-Hans leads
// with simplified-Chinese fonts — with a JP font first, shared Han codepoints
// render as Japanese glyph variants, which reads as wrong to zh-Hans users
// (persona-eval #327). Noto Sans JP stays in the stack for JP-only glyphs.
export const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  en: '"Noto Sans JP", ui-sans-serif, system-ui, sans-serif',
  ja: '"Noto Sans JP", "Hiragino Sans", "Yu Gothic UI", sans-serif',
  fr: '"Noto Sans JP", ui-sans-serif, system-ui, sans-serif',
  'zh-Hans': '"Noto Sans SC", "PingFang SC", "Microsoft YaHei", "Noto Sans JP", sans-serif',
  'pt-BR': '"Noto Sans JP", ui-sans-serif, system-ui, sans-serif',
  de: '"Noto Sans JP", ui-sans-serif, system-ui, sans-serif',
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
