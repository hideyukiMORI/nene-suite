export type MembershipRole = 'admin' | 'member' | 'viewer'

export interface OrganizationMember {
  membershipId: string
  operatorId: string
  email: string | null
  displayName: string | null
  role: MembershipRole
}
