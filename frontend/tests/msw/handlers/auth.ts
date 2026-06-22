import { http, HttpResponse } from 'msw'

interface CreateSessionBody {
  email?: unknown
  password?: unknown
}

interface SwitchOrgBody {
  organizationId?: unknown
}

const VALID_EMAIL = 'operator@example.com'
const VALID_PASSWORD = 's3cret-pass'

/** Organizations the test operator belongs to (drives the switcher). */
export const SESSION_ORG_ACME = {
  organizationId: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
  externalId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
  name: 'Acme KK',
  slug: 'acme-kk',
  role: 'admin' as const,
}
export const SESSION_ORG_BETA = {
  organizationId: '01J8XRNEWORG00000000000ZAB',
  externalId: '01J8XRNEWEXT00000000000ZAB',
  name: 'Beta LLC',
  slug: 'beta-llc',
  role: 'viewer' as const,
}

export const authHandlers = [
  http.post('/api/v1/auth/session', async ({ request }) => {
    const body = (await request.json()) as CreateSessionBody

    if (body.email === VALID_EMAIL && body.password === VALID_PASSWORD) {
      return HttpResponse.json(
        {
          token: 'test-token',
          expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
          operator: {
            id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
            email: VALID_EMAIL,
            displayName: 'Example Operator',
          },
          orgExternalId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
          role: 'admin',
          superadmin: true,
        },
        { status: 201 },
      )
    }

    return HttpResponse.json(
      {
        type: 'https://nene-suite.dev/problems/invalid-credentials',
        title: 'Invalid credentials',
        status: 401,
        detail: 'Invalid email or password.',
        instance: '/api/v1/auth/session',
      },
      { status: 401 },
    )
  }),
  http.delete('/api/v1/auth/session', () => new HttpResponse(null, { status: 204 })),
  http.get('/api/v1/auth/session/organizations', () =>
    HttpResponse.json({ organizations: [SESSION_ORG_ACME, SESSION_ORG_BETA] }),
  ),
  http.put('/api/v1/auth/session/active-organization', async ({ request }) => {
    const body = (await request.json()) as SwitchOrgBody
    const target =
      body.organizationId === SESSION_ORG_BETA.organizationId ? SESSION_ORG_BETA : SESSION_ORG_ACME

    return HttpResponse.json({
      token: 'switched-token',
      expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
      operator: {
        id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
        email: VALID_EMAIL,
        displayName: 'Example Operator',
      },
      orgExternalId: target.externalId,
      role: target.role,
      superadmin: true,
    })
  }),
]
