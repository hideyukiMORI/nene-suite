import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { AppLauncher } from './AppLauncher'

describe('AppLauncher', () => {
  it('renders installed apps with localized open links and SSOT labels', async () => {
    renderWithProviders(<AppLauncher />)

    await waitFor(() => {
      expect(screen.getByText('NeNe Invoice')).toBeInTheDocument()
    })

    const invoiceLink = screen.getByRole('link', { name: 'Open NeNe Invoice' })
    expect(invoiceLink).toHaveAttribute('href', 'https://example.com/nene-invoice/')
    expect(screen.getByText('Billing system of record')).toBeInTheDocument()
    expect(screen.getByText('Reconciliation evidence')).toBeInTheDocument()
  })
})
