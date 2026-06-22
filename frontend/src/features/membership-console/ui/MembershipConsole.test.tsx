import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { CONFLICT_OPERATOR } from '@tests/msw/handlers/membership'
import { mswServer } from '@tests/msw/server'
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

    await user.selectOptions(await screen.findByLabelText('Operator'), CONFLICT_OPERATOR)
    await user.click(screen.getByRole('button', { name: 'Add' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('already a member')
  })

  it('grants membership by picking an operator from the list', async () => {
    const user = userEvent.setup()
    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)

    const picker = await screen.findByLabelText('Operator')
    await user.selectOptions(picker, '01J8XRNEWOP000000000000ZAB')
    await user.click(screen.getByRole('button', { name: 'Add' }))

    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('labels a membership whose operator was removed instead of showing a raw id', async () => {
    const staleOperatorId = '01J8XRSTALE000000000000ZAB'
    mswServer.use(
      http.get('/api/v1/organizations/:id/memberships', () =>
        HttpResponse.json({
          members: [
            {
              membershipId: '01J8XRSTALEMEM0000000000ZA',
              operatorId: staleOperatorId,
              email: null,
              displayName: null,
              role: 'member',
            },
          ],
        }),
      ),
    )

    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)

    const cell = await screen.findByText(/Removed operator/)
    expect(cell).toHaveTextContent(staleOperatorId)
  })
})
