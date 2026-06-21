import {
  useOrganizationMembers,
  useGrantMembership,
  useChangeMembershipRole,
  useRevokeMembership,
  type ChangeMembershipRoleInput,
  type MembershipRole,
  type OrganizationMember,
} from '@/entities/membership'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface GrantMemberFields {
  operatorId: string
  role: MembershipRole
}

export interface UseMembershipConsoleResult {
  members: OrganizationMember[]
  isLoading: boolean
  isError: boolean
  grantMember: (fields: GrantMemberFields) => void
  changeRole: (input: ChangeMembershipRoleInput) => void
  revokeMember: (membershipId: string) => void
  isGranting: boolean
  isChanging: boolean
  isRevoking: boolean
  grantErrorKey: MessageKey | null
}

/** Glue between the membership entity hooks and the console UI (no JSX). */
export function useMembershipConsole(organizationId: string): UseMembershipConsoleResult {
  const query = useOrganizationMembers(organizationId)
  const grant = useGrantMembership(organizationId)
  const change = useChangeMembershipRole(organizationId)
  const revoke = useRevokeMembership(organizationId)

  return {
    members: query.data ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    grantMember: (fields) => {
      grant.mutate(fields)
    },
    changeRole: (input) => {
      change.mutate(input)
    },
    revokeMember: (membershipId) => {
      revoke.mutate(membershipId)
    },
    isGranting: grant.isPending,
    isChanging: change.isPending,
    isRevoking: revoke.isPending,
    grantErrorKey: grant.error !== null ? mapProblemDetailsToMessageKey(grant.error) : null,
  }
}
