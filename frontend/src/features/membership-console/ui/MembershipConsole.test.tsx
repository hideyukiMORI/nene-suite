import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { CONFLICT_OPERATOR } from '@tests/msw/handlers/membership'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { MembershipConsole } from './MembershipConsole'

const ORG_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC'

describe('MembershipConsole', () => {
  it('renders the enriched member list', async () => {
    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)

    expect(await screen.findByText('operator@example.com')).toBeInTheDocument()
  })

  it('shows a conflict message when granting an existing member', async () => {
    const user = userEvent.setup()
    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)
    await screen.findByText('operator@example.com')

    await user.type(screen.getByPlaceholderText(/01J8XR/), CONFLICT_OPERATOR)
    await user.click(screen.getByRole('button', { name: 'Add' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('already a member')
  })
})
