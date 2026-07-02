import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { LOCALE_STORAGE_KEY } from '@/shared/i18n'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { HelpGlossary } from './HelpGlossary'
import { HelpGuide } from './HelpGuide'
import { HelpLayout } from './HelpLayout'

describe('HelpLayout', () => {
  it('shows the Japanese-only body notice on non-ja locales with localized chrome', () => {
    renderWithProviders(
      <HelpLayout>
        <p>content</p>
      </HelpLayout>,
    )

    // Default test locale is English.
    expect(screen.getByRole('note')).toHaveTextContent(/available in Japanese only/)
    expect(screen.getByRole('complementary', { name: 'Help contents' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Help home' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Getting started' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Glossary' })).toBeInTheDocument()
  })

  it('hides the body notice on ja where the body is native', () => {
    localStorage.setItem(LOCALE_STORAGE_KEY, 'ja')
    renderWithProviders(
      <HelpLayout>
        <p>content</p>
      </HelpLayout>,
    )

    expect(screen.queryByRole('note')).not.toBeInTheDocument()
    expect(screen.getByRole('complementary', { name: 'ヘルプの目次' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'ヘルプの入口' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'はじめに' })).toBeInTheDocument()
  })
})

describe('HelpGlossary chrome', () => {
  it('localizes the glossary chrome in English while terms stay Japanese-first', () => {
    renderWithProviders(<HelpGlossary />)

    expect(screen.getByRole('heading', { name: 'Glossary' })).toBeInTheDocument()
    expect(screen.getByRole('searchbox', { name: 'Search terms' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Basics' })).toBeInTheDocument()
  })

  it('localizes the glossary chrome in Japanese', () => {
    localStorage.setItem(LOCALE_STORAGE_KEY, 'ja')
    renderWithProviders(<HelpGlossary />)

    expect(screen.getByRole('heading', { name: '用語集' })).toBeInTheDocument()
    expect(screen.getByRole('searchbox', { name: '用語を検索' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: '基本' })).toBeInTheDocument()
  })
})

describe('HelpGuide not-found chrome', () => {
  it('localizes the not-found state in English', () => {
    renderWithProviders(<HelpGuide slug="no-such-guide" />)

    expect(screen.getByRole('heading', { name: 'Page not found' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Back to help home' })).toBeInTheDocument()
  })

  it('localizes the not-found state in Japanese', () => {
    localStorage.setItem(LOCALE_STORAGE_KEY, 'ja')
    renderWithProviders(<HelpGuide slug="no-such-guide" />)

    expect(screen.getByRole('heading', { name: 'ページが見つかりません' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'ヘルプの入口へ戻る' })).toBeInTheDocument()
  })
})
