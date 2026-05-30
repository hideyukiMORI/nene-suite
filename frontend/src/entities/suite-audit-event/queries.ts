import {
  useInfiniteQuery,
  type InfiniteData,
  type UseInfiniteQueryResult,
} from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { SuiteAuditEventListDto } from './api-types'
import { toSuiteAuditEventPage } from './mapper'
import type { SuiteAuditEventPage } from './model'
import { suiteAuditEventKeys } from './query-keys'

const PAGE_SIZE = 50

/** Paginated orchestration audit trail (GET /api/v1/suite-audit-events). */
export function useSuiteAuditEvents(): UseInfiniteQueryResult<
  InfiniteData<SuiteAuditEventPage>,
  AppError
> {
  return useInfiniteQuery({
    queryKey: suiteAuditEventKeys.list(),
    queryFn: async ({ pageParam, signal }) => {
      const query = new URLSearchParams({ limit: String(PAGE_SIZE) })
      if (pageParam !== null) {
        query.set('cursor', pageParam)
      }
      const dto = await apiClient.get<SuiteAuditEventListDto>(
        `/api/v1/suite-audit-events?${query.toString()}`,
        signal,
      )
      return toSuiteAuditEventPage(dto)
    },
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => lastPage.nextCursor,
  })
}
