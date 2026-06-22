import type {
  AuthSessionDto,
  OperatorDto,
  SessionOrganizationDto,
  SessionOrganizationListDto,
} from './api-types'
import type { AuthSession, Operator, SessionOrganization } from './model'

export function toOperator(dto: OperatorDto): Operator {
  return {
    id: dto.id,
    email: dto.email,
    displayName: dto.displayName ?? null,
  }
}

export function toAuthSession(dto: AuthSessionDto): AuthSession {
  return {
    token: dto.token,
    expiresAt: dto.expiresAt,
    operator: toOperator(dto.operator),
    orgExternalId: dto.orgExternalId ?? null,
    role: dto.role ?? null,
    superadmin: dto.superadmin ?? false,
  }
}

export function toSessionOrganization(dto: SessionOrganizationDto): SessionOrganization {
  return {
    organizationId: dto.organizationId,
    externalId: dto.externalId,
    name: dto.name,
    slug: dto.slug,
    role: dto.role,
  }
}

export function toSessionOrganizations(dto: SessionOrganizationListDto): SessionOrganization[] {
  return dto.organizations.map(toSessionOrganization)
}
