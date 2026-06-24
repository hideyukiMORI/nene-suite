import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OriginFeedListDto } from './api-types'
import { toOriginFeeds } from './mapper'
import type { OriginFeeds } from './model'
import { originFeedKeys } from './query-keys'

/** Verified Origin announcements for the installed roster (GET /api/v1/origin/announcements). */
export function useOriginAnnouncements(locale: string): UseQueryResult<OriginFeeds, AppError> {
  return useQuery({
    queryKey: originFeedKeys.announcements(locale),
    queryFn: async ({ signal }) =>
      toOriginFeeds(
        await apiClient.get<OriginFeedListDto>(
          `/api/v1/origin/announcements?locale=${encodeURIComponent(locale)}`,
          signal,
        ),
      ),
  })
}

/** Verified Origin house-ads for the installed roster (GET /api/v1/origin/house-ads). */
export function useOriginHouseAds(locale: string): UseQueryResult<OriginFeeds, AppError> {
  return useQuery({
    queryKey: originFeedKeys.houseAds(locale),
    queryFn: async ({ signal }) =>
      toOriginFeeds(
        await apiClient.get<OriginFeedListDto>(
          `/api/v1/origin/house-ads?locale=${encodeURIComponent(locale)}`,
          signal,
        ),
      ),
  })
}
