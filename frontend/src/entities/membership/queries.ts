import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrganizationMemberListDto } from './api-types'
import { toOrganizationMembers } from './mapper'
import type { OrganizationMember } from './model'
import { membershipKeys } from './query-keys'

/** An organization's members, enriched with operator identity (GET .../memberships). */
export function useOrganizationMembers(
  organizationId: string,
): UseQueryResult<OrganizationMember[], AppError> {
  return useQuery({
    queryKey: membershipKeys.list(organizationId),
    queryFn: async ({ signal }) =>
      toOrganizationMembers(
        await apiClient.get<OrganizationMemberListDto>(
          `/api/v1/organizations/${organizationId}/memberships`,
          signal,
        ),
      ),
  })
}
