import { describe, expect, it } from 'vitest'
import type {
  AuthSessionDto,
  OperatorDto,
  SessionOrganizationDto,
  SessionOrganizationListDto,
} from './api-types'
import { toAuthSession, toOperator, toSessionOrganization, toSessionOrganizations } from './mapper'

const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA'

describe('toOperator', () => {
  it('maps id and email straight through', () => {
    const dto: OperatorDto = { id: OPERATOR_ID, email: 'operator@example.com' }

    expect(toOperator(dto)).toEqual({
      id: OPERATOR_ID,
      email: 'operator@example.com',
      displayName: null,
    })
  })

  it('keeps a present displayName', () => {
    const dto: OperatorDto = { id: OPERATOR_ID, email: 'op@example.com', displayName: 'Rina' }

    expect(toOperator(dto).displayName).toBe('Rina')
  })

  it('coerces an explicit null displayName to null', () => {
    const dto: OperatorDto = { id: OPERATOR_ID, email: 'op@example.com', displayName: null }

    expect(toOperator(dto).displayName).toBeNull()
  })

  it('defaults an absent displayName to null (never undefined)', () => {
    const dto: OperatorDto = { id: OPERATOR_ID, email: 'op@example.com' }
    const result = toOperator(dto)

    expect(result.displayName).toBeNull()
    // Absent-vs-null is normalized to null, so the property is always present.
    expect('displayName' in result).toBe(true)
  })
})

describe('toAuthSession', () => {
  const operator: OperatorDto = { id: OPERATOR_ID, email: 'operator@example.com' }

  it('maps a full session and nests the operator via toOperator', () => {
    const dto: AuthSessionDto = {
      token: 'jwt-token',
      expiresAt: '2026-07-18T12:00:00Z',
      operator: { ...operator, displayName: 'Rina' },
      orgExternalId: '01J8ORGKEY0000000000000000',
      role: 'admin',
      superadmin: true,
    }

    expect(toAuthSession(dto)).toEqual({
      token: 'jwt-token',
      expiresAt: '2026-07-18T12:00:00Z',
      operator: { id: OPERATOR_ID, email: 'operator@example.com', displayName: 'Rina' },
      orgExternalId: '01J8ORGKEY0000000000000000',
      role: 'admin',
      superadmin: true,
    })
  })

  it('fails closed when the org-context fields are absent (pre-A6 shape)', () => {
    const dto: AuthSessionDto = {
      token: 'jwt-token',
      expiresAt: '2026-07-18T12:00:00Z',
      operator,
    }
    const result = toAuthSession(dto)

    expect(result.orgExternalId).toBeNull()
    expect(result.role).toBeNull()
    expect(result.superadmin).toBe(false)
  })

  it('coerces explicit null org-context fields to null', () => {
    const dto: AuthSessionDto = {
      token: 'jwt-token',
      expiresAt: '2026-07-18T12:00:00Z',
      operator,
      orgExternalId: null,
      role: null,
    }
    const result = toAuthSession(dto)

    expect(result.orgExternalId).toBeNull()
    expect(result.role).toBeNull()
  })

  it('preserves a non-superadmin role without granting superadmin', () => {
    const dto: AuthSessionDto = {
      token: 'jwt-token',
      expiresAt: '2026-07-18T12:00:00Z',
      operator,
      role: 'viewer',
      superadmin: false,
    }
    const result = toAuthSession(dto)

    expect(result.role).toBe('viewer')
    expect(result.superadmin).toBe(false)
  })
})

describe('toSessionOrganization', () => {
  it('maps every field of one membership option', () => {
    const dto: SessionOrganizationDto = {
      organizationId: '01J8ORG00000000000000000AA',
      externalId: '01J8EXT00000000000000000BB',
      name: 'Acme KK',
      slug: 'acme',
      role: 'member',
    }

    expect(toSessionOrganization(dto)).toEqual(dto)
  })
})

describe('toSessionOrganizations', () => {
  it('maps a list of memberships', () => {
    const dto: SessionOrganizationListDto = {
      organizations: [
        {
          organizationId: '01J8ORG00000000000000000AA',
          externalId: '01J8EXT00000000000000000BB',
          name: 'Acme KK',
          slug: 'acme',
          role: 'admin',
        },
        {
          organizationId: '01J8ORG00000000000000000CC',
          externalId: '01J8EXT00000000000000000DD',
          name: 'Beta LLC',
          slug: 'beta',
          role: 'viewer',
        },
      ],
    }
    const result = toSessionOrganizations(dto)

    expect(result).toHaveLength(2)
    expect(result[0]?.slug).toBe('acme')
    expect(result[1]?.role).toBe('viewer')
  })

  it('returns an empty array when the operator belongs to no organizations', () => {
    const dto: SessionOrganizationListDto = { organizations: [] }

    expect(toSessionOrganizations(dto)).toEqual([])
  })
})
