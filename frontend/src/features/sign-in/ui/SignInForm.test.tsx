import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { SignInForm } from './SignInForm'

describe('SignInForm', () => {
  it('shows the empty-field message when submitting blank', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SignInForm />)

    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(await screen.findByText(/Enter your email and password/)).toBeInTheDocument()
  })

  it('shows a generic invalid-credentials error on a 401', async () => {
    const user = userEvent.setup()
    renderWithProviders(<SignInForm />)

    await user.type(screen.getByLabelText('Email'), 'operator@example.com')
    await user.type(screen.getByLabelText('Password'), 'wrong-password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(await screen.findByText('Invalid email or password')).toBeInTheDocument()
  })
})
