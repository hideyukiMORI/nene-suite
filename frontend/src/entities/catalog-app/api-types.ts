// Wire DTOs for the app catalog (docs/openapi/openapi.yaml CatalogAppList).

export type CatalogAppStatusDto = 'planned' | 'installable' | 'deprecated'

export interface CatalogAppDto {
  id: string
  name: string
  repository: string | null
  path: string
  status: CatalogAppStatusDto
  requires: string[]
  provides: string[]
  installEntry?: string
  databaseEnvPrefix?: string
}

export interface CatalogAppListDto {
  version: number
  apps: CatalogAppDto[]
}
