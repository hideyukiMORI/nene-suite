import { describe, it, expect } from 'vitest'
import { en } from './messages/en'
import { ja } from './messages/ja'
import { translate } from './translate'

describe('translate', () => {
  it('returns the message for an existing key', () => {
    expect(translate(en, 'suite.nav.home')).toBe('Home')
  })

  it('falls back to English when the key is missing from the locale catalog', () => {
    const partial = { 'suite.nav.home': 'Accueil' }
    expect(translate(partial, 'suite.nav.logout')).toBe(en['suite.nav.logout'])
  })

  it('interpolates {{param}} placeholders', () => {
    expect(translate(en, 'suite.install.apps.selectedCount', { count: 2 })).toBe('2 app(s) selected')
  })
})

describe('ja catalog', () => {
  it('uses Japanese when the key exists', () => {
    expect(translate(ja, 'suite.nav.home')).toBe('ホーム')
  })
})
