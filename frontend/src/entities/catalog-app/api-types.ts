// Wire DTOs for the app catalog — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type CatalogAppStatusDto = components['schemas']['CatalogApp']['status']
export type CatalogAppDto = components['schemas']['CatalogApp']
export type CatalogAppListDto = components['schemas']['CatalogAppList']
