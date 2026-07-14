import { describe, it, expect } from 'vitest'
import { DEFAULT_LOCALE, LOCALE_STORAGE_KEY, resolveLocale } from './locales'
import { en } from './messages/en'
import { ja } from './messages/ja'

describe('resolveLocale', () => {
  it('returns supported locales unchanged', () => {
    expect(resolveLocale('ja')).toBe('ja')
    expect(resolveLocale('en')).toBe('en')
  })

  it('resolves language-region tags', () => {
    expect(resolveLocale('ja-JP')).toBe('ja')
    expect(resolveLocale('en-GB')).toBe('en')
  })

  it('falls back to default for unknown locales', () => {
    expect(resolveLocale('unknown')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('zh-TW')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('zh-Hant')).toBe(DEFAULT_LOCALE)
  })

  it('falls back removed stub-locale catalogs (fr / zh-Hans / pt-BR / de) to en', () => {
    // Catalogs deleted 2026-07-14 (stub-only, parity-100%-or-delete). A
    // browser or stored localStorage value still carrying one of these
    // codes must degrade to en, not throw or resolve to an empty catalog.
    expect(resolveLocale('fr')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('zh-Hans')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('pt-BR')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('de')).toBe(DEFAULT_LOCALE)
  })
})

describe('LOCALE_STORAGE_KEY', () => {
  it('is suite-specific and distinct from nene-records', () => {
    expect(LOCALE_STORAGE_KEY).toBe('nene-suite-locale')
  })
})

describe('i18n key coverage', () => {
  it('ja contains every key defined in en', () => {
    const enKeys = Object.keys(en)
    const missingInJa = enKeys.filter((key) => !(key in ja))

    if (missingInJa.length > 0) {
      throw new Error(
        `ja.ts is missing ${String(missingInJa.length)} key(s):\n` +
          missingInJa.map((k) => `  • ${k}`).join('\n'),
      )
    }

    expect(missingInJa).toHaveLength(0)
  })

  it('all en keys use common.* or suite.* prefix', () => {
    const invalid = Object.keys(en).filter((key) => !/^(common|suite)\./.test(key))
    expect(invalid).toEqual([])
  })
})
