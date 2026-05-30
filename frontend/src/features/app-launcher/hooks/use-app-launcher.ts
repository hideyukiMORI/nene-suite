import { useInstalledApps, type InstalledApp } from '@/entities/installed-app'

export interface UseAppLauncherResult {
  apps: InstalledApp[]
  isLoading: boolean
  isError: boolean
}

/** Loads the installed-apps list for the apex launcher grid. */
export function useAppLauncher(): UseAppLauncherResult {
  const query = useInstalledApps()

  return {
    apps: query.data ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
  }
}
