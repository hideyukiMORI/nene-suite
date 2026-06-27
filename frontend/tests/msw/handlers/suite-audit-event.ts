import { http, HttpResponse } from 'msw'

const SUITE = '01J8XRDEV000000000000000ZA'
const ORG = '01JORGACME0000000000000000'

const PAGE_1 = [
  {
    id: '01J8XRADTQ9V2H7K3N5M0B8QG4',
    suiteId: SUITE,
    orgExternalId: ORG,
    actorUserId: '01JOPADMIN0000000000000000',
    actorLabel: 'admin@example.com',
    action: 'organization.renamed',
    entityType: 'organization',
    entityId: ORG,
    before: { name: 'Acme KK' },
    after: { name: 'Acme Corporation KK' },
    createdAt: '2026-06-27T09:14:03Z',
    source: 'apex_admin',
    requestId: 'req_8f2a1c',
    installSessionId: null,
    metadata: null,
  },
  {
    id: '01J8XRADTQ9V2H7K3N5M0B8QG3',
    suiteId: SUITE,
    orgExternalId: ORG,
    actorUserId: '01JOPADMIN0000000000000000',
    actorLabel: 'admin@example.com',
    action: 'membership.granted',
    entityType: 'membership',
    entityId: '01JMEMBER00000000000000B7',
    before: null,
    after: { operatorId: '01JOPSANAE0000000000000000', organizationId: ORG, role: 'member' },
    createdAt: '2026-06-27T07:20:11Z',
    source: 'apex_admin',
    requestId: 'req_aa1290',
    installSessionId: null,
    metadata: null,
  },
  {
    id: '01J8XRADTQ9V2H7K3N5M0B8QG2',
    suiteId: SUITE,
    orgExternalId: ORG,
    actorUserId: '01JOPADMIN0000000000000000',
    actorLabel: 'admin@example.com',
    action: 'membership.revoked',
    entityType: 'membership',
    entityId: '01JMEMBER00000000000000C2',
    before: { operatorId: '01JOPHIRO00000000000000000', organizationId: ORG, role: 'admin' },
    after: null,
    createdAt: '2026-06-27T06:55:00Z',
    source: 'apex_admin',
    requestId: 'req_bb7741',
    installSessionId: null,
    metadata: { reason: 'offboarding' },
  },
  {
    id: '01J8XRADTQ9V2H7K3N5M0B8QG1',
    suiteId: SUITE,
    orgExternalId: ORG,
    actorUserId: null,
    actorLabel: 'installer',
    action: 'env_config.written',
    entityType: 'suite_env_config',
    entityId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    before: null,
    after: { NENE_SUITE_ID: SUITE, NENE_SUITE_JWT_SECRET: '[REDACTED]' },
    createdAt: '2026-06-27T06:50:00Z',
    source: 'installer_ui',
    requestId: 'req_ff5566',
    installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    metadata: { note: 'secret-bearing values are sanitized to "[REDACTED]"' },
  },
]

const PAGE_2 = [
  {
    id: '01J8XRADTQ9V2H7K3N5M0B8QG0',
    suiteId: SUITE,
    orgExternalId: null,
    actorUserId: null,
    actorLabel: 'installer',
    action: 'install_session.started',
    entityType: 'install_session',
    entityId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    before: null,
    after: { status: 'in_progress', tier: 'B' },
    createdAt: '2026-06-27T06:40:00Z',
    source: 'installer_ui',
    requestId: null,
    installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
    metadata: null,
  },
]

export const suiteAuditEventHandlers = [
  http.get('/api/v1/suite-audit-events', ({ request }) => {
    const cursor = new URL(request.url).searchParams.get('cursor')
    if (cursor === null) {
      return HttpResponse.json({ items: PAGE_1, nextCursor: '01J8XRADTQ9V2H7K3N5M0B8QG1' })
    }
    return HttpResponse.json({ items: PAGE_2, nextCursor: null })
  }),
]
