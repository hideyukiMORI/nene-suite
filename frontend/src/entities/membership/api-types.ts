// Wire DTOs for organization memberships — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type OrganizationMemberDto = components['schemas']['OrganizationMember']
export type OrganizationMemberListDto = components['schemas']['OrganizationMemberList']
export type GrantMembershipRequestDto = components['schemas']['GrantMembershipRequest']
export type ChangeMembershipRoleRequestDto = components['schemas']['ChangeMembershipRoleRequest']
