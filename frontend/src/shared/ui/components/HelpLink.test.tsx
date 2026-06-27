import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { HelpLink } from './HelpLink'

describe('HelpLink', () => {
  it('links to the help guide for the given topic', () => {
    renderWithProviders(<HelpLink topic="install-wizard" />)
    const link = screen.getByRole('link', { name: /How to use this page/ })
    expect(link).toHaveAttribute('href', '/help/install-wizard')
  })
})
