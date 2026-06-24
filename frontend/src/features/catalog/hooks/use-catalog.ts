import { useCatalogApps, type CatalogApp } from '@/entities/catalog-app'
import { useInstalledApps } from '@/entities/installed-app'

export interface UseCatalogResult {
  apps: CatalogApp[]
  installedIds: Set<string>
  installedUrlById: Map<string, string>
  isLoading: boolean
  isError: boolean
  refetch: () => void
}

/**
 * Catalog roster + installed cross-reference. The catalog is the fatal source
 * (its error blocks the page); installed-apps is an enhancement (its absence
 * just means nothing shows as installed).
 */
export function useCatalog(): UseCatalogResult {
  const catalog = useCatalogApps()
  const installed = useInstalledApps()
  const installedApps = installed.data ?? []

  return {
    apps: catalog.data ?? [],
    installedIds: new Set(installedApps.map((app) => app.catalogId)),
    installedUrlById: new Map(installedApps.map((app) => [app.catalogId, app.publicUrl])),
    isLoading: catalog.isLoading || installed.isLoading,
    isError: catalog.isError,
    refetch: () => {
      void catalog.refetch()
      void installed.refetch()
    },
  }
}
