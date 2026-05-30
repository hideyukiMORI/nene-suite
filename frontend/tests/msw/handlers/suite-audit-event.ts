import { http, HttpResponse } from 'msw'

function event(id: string, action: string) {
  return {
    id,
    suiteId: '01J8XRDEV000000000000000ZA',
    orgExternalId: null,
    actorUserId: null,
    actorLabel: 'operator@example.com',
    action,
    entityType: 'install_session',
    entityId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    before: null,
    after: { status: 'in_progress' },
    createdAt: '2026-05-30T09:50:00Z',
    source: 'installer_ui',
    requestId: null,
    installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    metadata: null,
  }
}

export const suiteAuditEventHandlers = [
  http.get('/api/v1/suite-audit-events', ({ request }) => {
    const cursor = new URL(request.url).searchParams.get('cursor')

    if (cursor === null) {
      return HttpResponse.json({
        items: [
          event('01J8XRADTQ9V2H7K3N5M0B8QG2', 'install_session.completed'),
          event('01J8XRADTQ9V2H7K3N5M0B8QG1', 'disclaimer.accepted'),
        ],
        nextCursor: '01J8XRADTQ9V2H7K3N5M0B8QG1',
      })
    }

    return HttpResponse.json({
      items: [event('01J8XRADTQ9V2H7K3N5M0B8QG0', 'install_session.started')],
      nextCursor: null,
    })
  }),
]
