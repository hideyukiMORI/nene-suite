import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OrganizationListDto } from './api-types'
import { toOrganizations } from './mapper'
import type { Organization } from './model'
import { organizationKeys } from './query-keys'

/** All tenant organizations (GET /api/v1/organizations). Platform-superadmin only. */
export function useOrganizations(): UseQueryResult<Organization[], AppError> {
  return useQuery({
    queryKey: organizationKeys.list(),
    queryFn: async ({ signal }) =>
      toOrganizations(await apiClient.get<OrganizationListDto>('/api/v1/organizations', signal)),
  })
}
