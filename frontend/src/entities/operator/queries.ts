import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OperatorListDto } from './api-types'
import { toOperators } from './mapper'
import type { Operator } from './model'
import { operatorKeys } from './query-keys'

/** All apex operators (GET /api/v1/operators). Platform-superadmin only. */
export function useOperators(): UseQueryResult<Operator[], AppError> {
  return useQuery({
    queryKey: operatorKeys.list(),
    queryFn: async ({ signal }) =>
      toOperators(await apiClient.get<OperatorListDto>('/api/v1/operators', signal)),
  })
}
