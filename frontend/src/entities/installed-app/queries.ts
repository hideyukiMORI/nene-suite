import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InstalledAppListDto } from './api-types'
import { toInstalledApp } from './mapper'
import type { InstalledApp } from './model'
import { installedAppKeys } from './query-keys'

/** Apps installed through the suite, for the apex launcher (GET /api/v1/installed-apps). */
export function useInstalledApps(): UseQueryResult<InstalledApp[], AppError> {
  return useQuery({
    queryKey: installedAppKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<InstalledAppListDto>('/api/v1/installed-apps', signal)
      return dto.apps.map(toInstalledApp)
    },
  })
}
