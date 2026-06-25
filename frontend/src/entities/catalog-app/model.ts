export type CatalogAppStatus = 'planned' | 'installable' | 'deprecated'

export interface CatalogApp {
  id: string
  name: string
  status: CatalogAppStatus
  requires: string[]
  provides: string[]
  /** Installed version mirrored from Origin (ADR 0013 §4); null when unknown. */
  installedVersion: string | null
  /** Latest available version mirrored from Origin; null when unknown. */
  availableVersion: string | null
}
