import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OperatorDto } from './api-types'
import { toOperator } from './mapper'
import type { Operator } from './model'
import { authKeys } from './query-keys'

/** Current operator for the presented token (GET /api/v1/auth/session). */
export function useCurrentOperator(enabled = true): UseQueryResult<Operator, AppError> {
  return useQuery({
    queryKey: authKeys.session(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<OperatorDto>('/api/v1/auth/session', signal)
      return toOperator(dto)
    },
    enabled,
  })
}
