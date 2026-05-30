// Wire DTOs for the apex launcher (docs/openapi/openapi.yaml InstalledAppList).

export type SsotRoleDto = 'billing' | 'reconciliation_evidence' | 'cms' | 'archive' | 'none'

export interface InstalledAppDto {
  catalogId: string
  name: string
  publicUrl: string
  databaseName: string | null
  ssotRole: SsotRoleDto
}

export interface InstalledAppListDto {
  apps: InstalledAppDto[]
}
