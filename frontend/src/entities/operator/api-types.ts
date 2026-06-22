// Wire DTOs for apex operators — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OperatorDto = components['schemas']['Operator']
export type OperatorListDto = components['schemas']['OperatorList']
