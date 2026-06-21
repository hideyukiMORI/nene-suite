// Wire DTOs for tenant organizations — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OrganizationDto = components['schemas']['Organization']
export type OrganizationListDto = components['schemas']['OrganizationList']
export type CreateOrganizationRequestDto = components['schemas']['CreateOrganizationRequest']
export type RenameOrganizationRequestDto = components['schemas']['RenameOrganizationRequest']
