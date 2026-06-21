import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CreateOrganizationRequestDto, OrganizationDto } from './api-types'
import { toOrganization } from './mapper'
import type { Organization } from './model'
import { organizationKeys } from './query-keys'

function useOrganizationWriter<TInput>(
  send: (input: TInput) => Promise<OrganizationDto>,
): UseMutationResult<Organization, AppError, TInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: TInput) => toOrganization(await send(input)),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: organizationKeys.list() })
    },
  })
}

/** POST /api/v1/organizations */
export function useCreateOrganization(): UseMutationResult<
  Organization,
  AppError,
  CreateOrganizationRequestDto
> {
  return useOrganizationWriter((input) =>
    apiClient.post<OrganizationDto>('/api/v1/organizations', input),
  )
}

export interface RenameOrganizationInput {
  id: string
  name: string
}

/** PATCH /api/v1/organizations/{id} */
export function useRenameOrganization(): UseMutationResult<
  Organization,
  AppError,
  RenameOrganizationInput
> {
  return useOrganizationWriter(({ id, name }) =>
    apiClient.patch<OrganizationDto>(`/api/v1/organizations/${id}`, { name }),
  )
}

/** POST /api/v1/organizations/{id}/disable */
export function useDisableOrganization(): UseMutationResult<Organization, AppError, string> {
  return useOrganizationWriter((id: string) =>
    apiClient.post<OrganizationDto>(`/api/v1/organizations/${id}/disable`, {}),
  )
}
