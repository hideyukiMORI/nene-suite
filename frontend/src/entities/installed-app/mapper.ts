import type { InstalledAppDto } from './api-types'
import type { InstalledApp } from './model'

export function toInstalledApp(dto: InstalledAppDto): InstalledApp {
  return {
    catalogId: dto.catalogId,
    name: dto.name,
    publicUrl: dto.publicUrl,
    databaseName: dto.databaseName ?? null,
    ssotRole: dto.ssotRole,
  }
}
