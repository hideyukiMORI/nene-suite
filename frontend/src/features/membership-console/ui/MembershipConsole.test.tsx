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

  it('shows which organization is being edited (name + slug)', async () => {
    renderWithProviders(<MembershipConsole organizationId="01J8XR0G7Q9V2H7K3N5M0B8TCA" />)

    const banner = await screen.findByRole('region', { name: 'Target organization' })
    expect(banner).toHaveTextContent('Acme KK')
    expect(banner).toHaveTextContent('acme-kk')
  })

  it('flags a disabled organization in the context banner', async () => {
    renderWithProviders(<MembershipConsole organizationId="01J8XR0G7Q9V2H7K3N5M0B8TCB" />)

    const banner = await screen.findByRole('region', { name: 'Target organization' })
    expect(banner).toHaveTextContent('Umbrella KK')
    expect(banner).toHaveTextContent('Disabled')
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

  it('protects the last admin: disables demote options and revoke, and shows a hint', async () => {
    // The default handler returns a single admin — i.e. the org's last admin.
    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)
    await screen.findByText('operator@example.com')

    expect(screen.getByRole('button', { name: "Why can't I change this?" })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Remove' })).toBeDisabled()

    const memberOptions = screen.getAllByRole('option', { name: 'Member' })
    expect(memberOptions.some((option) => option.hasAttribute('disabled'))).toBe(true)
  })

  it('surfaces the 409 invariant message when a demote is rejected by the server', async () => {
    const user = userEvent.setup()
    mswServer.use(
      http.get('/api/v1/organizations/:id/memberships', () =>
        HttpResponse.json({
          members: [
            {
              membershipId: '01J8XRADMIN10000000000000A',
              operatorId: '01J8XROP1000000000000000AA',
              email: 'admin-a@example.com',
              displayName: null,
              role: 'admin',
            },
            {
              membershipId: '01J8XRADMIN20000000000000B',
              operatorId: '01J8XROP2000000000000000BB',
              email: 'admin-b@example.com',
              displayName: null,
              role: 'admin',
            },
          ],
        }),
      ),
      http.patch('/api/v1/memberships/:id', () =>
        HttpResponse.json(
          {
            type: 'https://nene-suite.dev/problems/membership-invariant',
            title: 'Membership invariant violated',
            status: 409,
            detail: 'Organization must retain at least one admin.',
          },
          { status: 409 },
        ),
      ),
    )

    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)
    await screen.findByText('admin-a@example.com')

    // With two admins a demote is allowed by the UI; the server still rejects it.
    const adminSelect = screen
      .getAllByRole('combobox')
      .find((element) => element instanceof HTMLSelectElement && element.value === 'admin')
    if (!(adminSelect instanceof HTMLSelectElement)) {
      throw new Error('expected an admin role select')
    }
    await user.selectOptions(adminSelect, 'Member')

    expect(await screen.findByRole('alert')).toHaveTextContent('at least one admin')
  })

  it('keeps the grant form input after a conflict so the user can retry', async () => {
    const user = userEvent.setup()
    renderWithProviders(<MembershipConsole organizationId={ORG_ID} />)
    await screen.findByText('operator@example.com')

    const picker = await screen.findByLabelText('Operator')
    await user.selectOptions(picker, CONFLICT_OPERATOR)
    await user.click(screen.getByRole('button', { name: 'Add' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('already a member')
    expect(picker).toHaveValue(CONFLICT_OPERATOR)
  })
})
