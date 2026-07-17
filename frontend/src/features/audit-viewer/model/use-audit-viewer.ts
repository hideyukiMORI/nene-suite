import { useSuiteAuditEvents, type SuiteAuditEvent } from '@/entities/suite-audit-event'

export interface UseAuditViewerResult {
  events: SuiteAuditEvent[]
  isLoading: boolean
  isError: boolean
  hasMore: boolean
  isLoadingMore: boolean
  loadMore: () => void
  refetch: () => void
}

/** Flattens the paginated audit query into a single list for the table. */
export function useAuditViewer(): UseAuditViewerResult {
  const query = useSuiteAuditEvents()
  const events = query.data?.pages.flatMap((page) => page.items) ?? []

  return {
    events,
    isLoading: query.isLoading,
    isError: query.isError,
    hasMore: query.hasNextPage,
    isLoadingMore: query.isFetchingNextPage,
    loadMore: () => {
      void query.fetchNextPage()
    },
    refetch: () => {
      void query.refetch()
    },
  }
}
