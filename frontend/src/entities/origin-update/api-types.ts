// Wire DTOs for the Origin update signals — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OriginUpdateDto = components['schemas']['OriginUpdate']
export type OriginUpdateListDto = components['schemas']['OriginUpdateList']
