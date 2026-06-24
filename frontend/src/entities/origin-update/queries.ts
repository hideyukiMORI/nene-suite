import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OriginUpdateListDto } from './api-types'
import { toOriginUpdates } from './mapper'
import type { OriginUpdates } from './model'
import { originUpdateKeys } from './query-keys'

/** Verified Origin update signals for the installed roster (GET /api/v1/origin/updates). */
export function useOriginUpdates(): UseQueryResult<OriginUpdates, AppError> {
  return useQuery({
    queryKey: originUpdateKeys.list(),
    queryFn: async ({ signal }) =>
      toOriginUpdates(await apiClient.get<OriginUpdateListDto>('/api/v1/origin/updates', signal)),
  })
}
