import { http, HttpResponse } from 'msw'

interface GrantBody {
  operatorId?: unknown
  role?: unknown
}

/** Sentinel operatorId that the grant handler rejects with a 409 membership-conflict. */
export const CONFLICT_OPERATOR = '01J8XRCONFLICT0000000000ZA'

export const membershipHandlers = [
  http.get('/api/v1/organizations/:id/memberships', () =>
    HttpResponse.json({
      members: [
        {
          membershipId: '01J8XRMEM00000000000000ZAB',
          operatorId: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
          email: 'operator@example.com',
          displayName: 'Example Operator',
          role: 'admin',
        },
      ],
    }),
  ),
  http.post('/api/v1/organizations/:id/memberships', async ({ request }) => {
    const body = (await request.json()) as GrantBody

    if (body.operatorId === CONFLICT_OPERATOR) {
      return HttpResponse.json(
        {
          type: 'https://nene-suite.dev/problems/membership-conflict',
          title: 'Membership already exists',
          status: 409,
          detail: 'The operator already has a membership in this scope.',
          instance: '/api/v1/organizations/01J8XR4ZS6Q9V2H7K3N5M0B8TC/memberships',
        },
        { status: 409 },
      )
    }

    return HttpResponse.json(
      {
        id: '01J8XRNEWMEM0000000000000A',
        operatorId: typeof body.operatorId === 'string' ? body.operatorId : 'unknown',
        organizationId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
        role: typeof body.role === 'string' ? body.role : 'member',
      },
      { status: 201 },
    )
  }),
  http.patch('/api/v1/memberships/:id', ({ params }) =>
    HttpResponse.json({
      id: String(params.id),
      operatorId: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
      organizationId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
      role: 'member',
    }),
  ),
  http.delete('/api/v1/memberships/:id', () => new HttpResponse(null, { status: 204 })),
]
