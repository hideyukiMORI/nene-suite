import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { HelpText } from './HelpText'

describe('HelpText', () => {
  it('links a known identifier to its glossary entry', () => {
    renderWithProviders(<HelpText text="provision を選びます" />)
    const link = screen.getByRole('link', { name: 'provision' })
    expect(link).toHaveAttribute('href', '/help/glossary#gt-provision')
  })

  it('leaves prose without identifiers as plain text', () => {
    renderWithProviders(<HelpText text="ふつうの文章です" />)
    expect(screen.getByText('ふつうの文章です')).toBeInTheDocument()
    expect(screen.queryByRole('link')).not.toBeInTheDocument()
  })
})
