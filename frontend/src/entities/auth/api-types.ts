// Wire DTOs for the apex auth session — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OperatorDto = components['schemas']['Operator']
export type CreateAuthSessionRequestDto = components['schemas']['CreateAuthSessionRequest']
export type AuthSessionDto = components['schemas']['AuthSession']
