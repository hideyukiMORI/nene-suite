import { describe, expect, it } from 'vitest'
import { GLOSSARY } from './glossary'
import { GUIDES } from './guides'
import { searchHelp } from './search'

describe('searchHelp', () => {
  it('returns every guide and term for an empty query', () => {
    const result = searchHelp('')
    expect(result.guides).toHaveLength(GUIDES.length)
    expect(result.terms).toHaveLength(GLOSSARY.length)
  })

  it('filters glossary terms by keyword', () => {
    const result = searchHelp('provision')
    expect(result.terms.some((term) => term.id === 'provision')).toBe(true)
    expect(result.terms.some((term) => term.id === 'audit-event')).toBe(false)
  })

  it('matches guides by their title or body', () => {
    const result = searchHelp('インストール')
    expect(result.guides.some((guide) => guide.slug === 'install-wizard')).toBe(true)
  })

  it('returns nothing for an unmatched query', () => {
    const result = searchHelp('zzzz-not-a-term-zzzz')
    expect(result.guides).toHaveLength(0)
    expect(result.terms).toHaveLength(0)
  })
})
