export const membershipKeys = {
  all: ['memberships'] as const,
  list: (organizationId: string) => [...membershipKeys.all, 'list', organizationId] as const,
}
