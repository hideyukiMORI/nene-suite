import type { AuthSessionDto, OperatorDto } from './api-types'
import type { AuthSession, Operator } from './model'

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
