export type CatalogAppStatus = 'planned' | 'installable' | 'deprecated'

export interface CatalogApp {
  id: string
  name: string
  status: CatalogAppStatus
  requires: string[]
  provides: string[]
}
