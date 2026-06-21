import type { OrganizationDto, OrganizationListDto } from './api-types'
import type { Organization } from './model'

export function toOrganization(dto: OrganizationDto): Organization {
  return {
    id: dto.id,
    externalId: dto.externalId,
    name: dto.name,
    slug: dto.slug,
    status: dto.status,
  }
}

export function toOrganizations(dto: OrganizationListDto): Organization[] {
  return dto.organizations.map(toOrganization)
}
