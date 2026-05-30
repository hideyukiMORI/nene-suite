import { http, HttpResponse } from 'msw'

interface MockSession {
  id: string
  suiteId: string
  status: string
  tier: string
  catalogRevision: number
  selectedApps: string[]
  disclaimerAccepted: boolean
  disclaimerAcceptedAt: string | null
  orgExternalId: string | null
  orgDisplayName: string | null
  installManifestId: string | null
  failureCode: string | null
  createdAt: string
  updatedAt: string
  completedAt: string | null
}

const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC'
let session: MockSession | null = null

function newSession(): MockSession {
  const now = new Date().toISOString()
  return {
    id: SESSION_ID,
    suiteId: '01J8XRDEV000000000000000ZA',
    status: 'in_progress',
    tier: 'B',
    catalogRevision: 1,
    selectedApps: [],
    disclaimerAccepted: false,
    disclaimerAcceptedAt: null,
    orgExternalId: null,
    orgDisplayName: null,
    installManifestId: null,
    failureCode: null,
    createdAt: now,
    updatedAt: now,
    completedAt: null,
  }
}

/** Reset the in-memory install session between tests. */
export function resetInstallSessionState(): void {
  session = null
}

export const installSessionHandlers = [
  http.post('/api/v1/install-sessions', () => {
    session = newSession()
    return HttpResponse.json(session, { status: 201 })
  }),
  http.get('/api/v1/install-sessions/:id', () =>
    session === null ? new HttpResponse(null, { status: 404 }) : HttpResponse.json(session),
  ),
  http.put('/api/v1/install-sessions/:id/app-selection', async ({ request }) => {
    const body = (await request.json()) as { selectedApps?: string[] }
    if (session !== null) {
      session = { ...session, selectedApps: body.selectedApps ?? [] }
    }
    return HttpResponse.json(session)
  }),
  http.post('/api/v1/install-sessions/:id/disclaimer-acceptance', () => {
    if (session !== null) {
      session = {
        ...session,
        disclaimerAccepted: true,
        disclaimerAcceptedAt: new Date().toISOString(),
      }
    }
    return HttpResponse.json(session)
  }),
  http.post('/api/v1/install-sessions/:id/complete', () => {
    if (session !== null) {
      session = { ...session, status: 'completed', installManifestId: '01J8XR9MAQ9V2H7K3N5M0B8DFG' }
    }
    return HttpResponse.json(session)
  }),
]
