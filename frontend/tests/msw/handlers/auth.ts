import { http, HttpResponse } from 'msw'

interface CreateSessionBody {
  email?: unknown
  password?: unknown
}

const VALID_EMAIL = 'operator@example.com'
const VALID_PASSWORD = 's3cret-pass'

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
]
