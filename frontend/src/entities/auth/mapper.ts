import type { AuthSessionDto, OperatorDto } from './api-types'
import type { AuthSession, Operator } from './model'

export function toOperator(dto: OperatorDto): Operator {
  return {
    id: dto.id,
    email: dto.email,
    displayName: dto.displayName,
  }
}

export function toAuthSession(dto: AuthSessionDto): AuthSession {
  return {
    token: dto.token,
    expiresAt: dto.expiresAt,
    operator: toOperator(dto.operator),
  }
}
