import type { OrganizationMemberDto, OrganizationMemberListDto } from './api-types'
import type { OrganizationMember } from './model'

export function toOrganizationMember(dto: OrganizationMemberDto): OrganizationMember {
  return {
    membershipId: dto.membershipId,
    operatorId: dto.operatorId,
    email: dto.email,
    displayName: dto.displayName,
    role: dto.role,
  }
}

export function toOrganizationMembers(dto: OrganizationMemberListDto): OrganizationMember[] {
  return dto.members.map(toOrganizationMember)
}
