import { describe, expect, it } from 'vitest'
import { applyLocaleFontFamily, LOCALE_FONT_FAMILY_VAR, LOCALE_FONT_STACKS } from './locale-fonts'
import { SUPPORTED_LOCALE_IDS } from './locales'

describe('LOCALE_FONT_STACKS', () => {
  it('defines a stack for every supported locale', () => {
    for (const id of SUPPORTED_LOCALE_IDS) {
      expect(LOCALE_FONT_STACKS[id]).toContain('sans-serif')
    }
  })

  it('leads ja and en with Noto Sans JP (design consistency)', () => {
    expect(LOCALE_FONT_STACKS.ja.startsWith('"Noto Sans JP"')).toBe(true)
    expect(LOCALE_FONT_STACKS.en.startsWith('"Noto Sans JP"')).toBe(true)
  })

  it('leads zh-Hans with simplified-Chinese fonts, not JP glyph variants', () => {
    const stack = LOCALE_FONT_STACKS['zh-Hans']
    expect(stack.startsWith('"Noto Sans SC"')).toBe(true)
    // JP coverage stays available further down the stack
    expect(stack.indexOf('Noto Sans JP')).toBeGreaterThan(stack.indexOf('Noto Sans SC'))
  })
})

describe('applyLocaleFontFamily', () => {
  it('writes the locale stack onto the font CSS variable', () => {
    const root = document.createElement('div')

    applyLocaleFontFamily('zh-Hans', root)

    expect(root.style.getPropertyValue(LOCALE_FONT_FAMILY_VAR)).toBe(LOCALE_FONT_STACKS['zh-Hans'])
  })
})
