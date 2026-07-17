import {
  deriveCardState,
  useCatalogApps,
  type CatalogApp,
  type CatalogCardState,
} from '@/entities/catalog-app'
import { useInstalledApps, type InstalledApp } from '@/entities/installed-app'

export interface AppDetail {
  app: CatalogApp
  installed: InstalledApp | null
  state: CatalogCardState
}

/** Resolves a catalog app + its installed record (if any) + derived state. */
export function useAppDetail(appId: string): AppDetail | null {
  const catalog = useCatalogApps()
  const installed = useInstalledApps()

  const app = (catalog.data ?? []).find((entry) => entry.id === appId)
  if (app === undefined) return null

  const installedApps = installed.data ?? []
  const installedApp = installedApps.find((entry) => entry.catalogId === appId) ?? null
  const installedIds = new Set(installedApps.map((entry) => entry.catalogId))

  return { app, installed: installedApp, state: deriveCardState(app, installedIds) }
}
