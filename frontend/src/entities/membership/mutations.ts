import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { GrantMembershipRequestDto } from './api-types'
import type { MembershipRole } from './model'
import { membershipKeys } from './query-keys'

function useMembershipWriter<TInput>(
  organizationId: string,
  send: (input: TInput) => Promise<unknown>,
): UseMutationResult<void, AppError, TInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: TInput) => {
      await send(input)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: membershipKeys.list(organizationId) })
    },
  })
}

/** POST /api/v1/organizations/{id}/memberships */
export function useGrantMembership(
  organizationId: string,
): UseMutationResult<void, AppError, GrantMembershipRequestDto> {
  return useMembershipWriter(organizationId, (input) =>
    apiClient.post<unknown>(`/api/v1/organizations/${organizationId}/memberships`, input),
  )
}

export interface ChangeMembershipRoleInput {
  membershipId: string
  role: MembershipRole
}

/** PATCH /api/v1/memberships/{id} */
export function useChangeMembershipRole(
  organizationId: string,
): UseMutationResult<void, AppError, ChangeMembershipRoleInput> {
  return useMembershipWriter(organizationId, ({ membershipId, role }) =>
    apiClient.patch<unknown>(`/api/v1/memberships/${membershipId}`, { role }),
  )
}

/** DELETE /api/v1/memberships/{id} */
export function useRevokeMembership(
  organizationId: string,
): UseMutationResult<void, AppError, string> {
  return useMembershipWriter(organizationId, (membershipId: string) =>
    apiClient.delete(`/api/v1/memberships/${membershipId}`),
  )
}
