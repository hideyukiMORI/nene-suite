// Wire DTOs for the apex launcher — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type SsotRoleDto = components['schemas']['SsotRole']
export type InstalledAppDto = components['schemas']['InstalledApp']
export type InstalledAppListDto = components['schemas']['InstalledAppList']
