import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CatalogAppListDto } from './api-types'
import { toCatalogApp } from './mapper'
import type { CatalogApp } from './model'
import { catalogAppKeys } from './query-keys'

/** Installable app catalog with dependency graph (GET /api/v1/catalog/apps). */
export function useCatalogApps(): UseQueryResult<CatalogApp[], AppError> {
  return useQuery({
    queryKey: catalogAppKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<CatalogAppListDto>('/api/v1/catalog/apps', signal)
      return dto.apps.map(toCatalogApp)
    },
    staleTime: 5 * 60 * 1000,
  })
}
