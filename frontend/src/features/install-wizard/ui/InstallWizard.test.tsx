import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it } from 'vitest'
import { resetInstallSessionState } from '@tests/msw/handlers/install-session'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { InstallWizard } from './InstallWizard'

describe('InstallWizard', () => {
  beforeEach(() => {
    resetInstallSessionState()
  })

  it('marks the current step with aria-current and announces progress', async () => {
    const user = userEvent.setup()
    renderWithProviders(<InstallWizard />)

    await user.click(screen.getByRole('button', { name: 'Start installer' }))

    const currentStep = await screen.findByText('Select apps')
    expect(currentStep.closest('li')).toHaveAttribute('aria-current', 'step')
    expect(screen.getByRole('status')).toHaveTextContent('Step 1 of 5: Select apps')

    await user.click(screen.getByRole('checkbox', { name: 'NeNe Invoice' }))
    await user.click(screen.getByRole('button', { name: 'Next' }))

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('Step 2 of 5: Databases')
    })
    const databaseStep = screen.getByText('Databases', { selector: 'li span' })
    expect(databaseStep.closest('li')).toHaveAttribute('aria-current', 'step')
    expect(screen.getByText('Select apps').closest('li')).not.toHaveAttribute('aria-current')
  })

  it('navigates back from the database step with the selection prefilled', async () => {
    const user = userEvent.setup()
    renderWithProviders(<InstallWizard />)
    await user.click(screen.getByRole('button', { name: 'Start installer' }))
    await user.click(await screen.findByRole('checkbox', { name: 'NeNe Invoice' }))
    await user.click(screen.getByRole('button', { name: 'Next' }))
    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('Step 2 of 5')
    })

    await user.click(screen.getByRole('button', { name: 'Back' }))

    expect(screen.getByRole('status')).toHaveTextContent('Step 1 of 5: Select apps')
    expect(screen.getByRole('checkbox', { name: 'NeNe Invoice' })).toBeChecked()
  })

  it('submits the database step with Enter from a text field', async () => {
    const user = userEvent.setup()
    renderWithProviders(<InstallWizard />)
    await user.click(screen.getByRole('button', { name: 'Start installer' }))
    await user.click(await screen.findByRole('checkbox', { name: 'NeNe Invoice' }))
    await user.click(screen.getByRole('button', { name: 'Next' }))
    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('Step 2 of 5')
    })

    await user.click(screen.getByRole('radio', { name: 'Adopt existing' }))
    await user.type(screen.getByLabelText(/Server/), 'db.internal{Enter}')

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('Step 3 of 5')
    })
  })
})
