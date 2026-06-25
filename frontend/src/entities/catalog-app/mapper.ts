import type { CatalogAppDto } from './api-types'
import type { CatalogApp } from './model'

export function toCatalogApp(dto: CatalogAppDto): CatalogApp {
  return {
    id: dto.id,
    name: dto.name,
    status: dto.status,
    requires: dto.requires,
    provides: dto.provides,
    installedVersion: dto.installedVersion ?? null,
    availableVersion: dto.availableVersion ?? null,
  }
}
