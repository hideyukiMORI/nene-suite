import type { OperatorDto, OperatorListDto } from './api-types'
import type { Operator } from './model'

export function toOperator(dto: OperatorDto): Operator {
  return {
    id: dto.id,
    email: dto.email,
    displayName: dto.displayName ?? null,
  }
}

export function toOperators(dto: OperatorListDto): Operator[] {
  return dto.operators.map(toOperator)
}
