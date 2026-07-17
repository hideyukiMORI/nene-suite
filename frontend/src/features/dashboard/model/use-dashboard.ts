import { useInstalledApps, type InstalledApp } from '@/entities/installed-app'

export interface UseDashboardResult {
  apps: InstalledApp[]
  isLoading: boolean
  isError: boolean
  refetch: () => void
}

/** Loads the installed-apps list that backs the home dashboard (pillars + KPI). */
export function useDashboard(): UseDashboardResult {
  const query = useInstalledApps()
  return {
    apps: query.data ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    refetch: () => {
      void query.refetch()
    },
  }
}
