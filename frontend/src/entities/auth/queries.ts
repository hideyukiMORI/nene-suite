import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OperatorDto, SessionOrganizationListDto } from './api-types'
import { toOperator, toSessionOrganizations } from './mapper'
import { authStore, type Operator, type SessionOrganization } from './model'
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

/** Organizations the signed-in operator belongs to (GET /api/v1/auth/session/organizations). */
export function useSessionOrganizations(): UseQueryResult<SessionOrganization[], AppError> {
  return useQuery({
    queryKey: authKeys.organizations(),
    queryFn: async ({ signal }) =>
      toSessionOrganizations(
        await apiClient.get<SessionOrganizationListDto>(
          '/api/v1/auth/session/organizations',
          signal,
        ),
      ),
    enabled: authStore.isAuthenticated(),
  })
}
