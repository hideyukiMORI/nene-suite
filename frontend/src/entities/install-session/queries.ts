import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InstallSessionDto } from './api-types'
import { toInstallSession } from './mapper'
import type { InstallSession } from './model'
import { installSessionKeys } from './query-keys'

/** Reads one install session (GET /api/v1/install-sessions/{id}). */
export function useInstallSession(
  sessionId: string | null,
): UseQueryResult<InstallSession, AppError> {
  return useQuery({
    queryKey: installSessionKeys.detail(sessionId ?? ''),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<InstallSessionDto>(
        `/api/v1/install-sessions/${sessionId ?? ''}`,
        signal,
      )
      return toInstallSession(dto)
    },
    enabled: sessionId !== null,
  })
}
