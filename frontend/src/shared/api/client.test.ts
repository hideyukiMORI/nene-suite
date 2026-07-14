import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { authStore } from '@/entities/auth/model'
import { mswServer } from '@tests/msw/server'
import { apiClient, AppError } from './client'

function seedSession(): void {
  authStore.setSession({
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600 * 1000).toISOString(),
    operator: {
      id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
      email: 'operator@example.com',
      displayName: null,
    },
    orgExternalId: null,
    role: null,
    superadmin: true,
  })
}

describe('apiClient (nene2-client transport adapter)', () => {
  it('mirrors the bearer token on both Authorization and X-Authorization for a representative request', async () => {
    seedSession()
    let seenAuthorization: string | null = null
    let seenMirror: string | null = null

    mswServer.use(
      http.get('/api/v1/probe', ({ request }) => {
        seenAuthorization = request.headers.get('Authorization')
        seenMirror = request.headers.get('X-Authorization')
        return HttpResponse.json({ ok: true })
      }),
    )

    const result = await apiClient.get<{ ok: boolean }>('/api/v1/probe')

    expect(result).toEqual({ ok: true })
    expect(seenAuthorization).toBe('Bearer test-token')
    expect(seenMirror).toBe('Bearer test-token')
  })

  it('maps a non-2xx response to AppError and clears the session on 401', async () => {
    seedSession()

    mswServer.use(
      http.get('/api/v1/probe', () =>
        HttpResponse.json(
          {
            type: 'https://nene-suite.dev/problems/session-expired',
            title: 'Session expired',
            status: 401,
            detail: 'Sign in again.',
          },
          { status: 401 },
        ),
      ),
    )

    await expect(apiClient.get('/api/v1/probe')).rejects.toMatchObject({
      status: 401,
      type: 'https://nene-suite.dev/problems/session-expired',
    })
    await expect(apiClient.get('/api/v1/probe')).rejects.toBeInstanceOf(AppError)
    expect(authStore.getSession()).toBeNull()
  })
})
