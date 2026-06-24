// Wire DTOs for the Origin content-tree feeds — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OriginFeedDto = components['schemas']['OriginFeed']
export type OriginFeedListDto = components['schemas']['OriginFeedList']
