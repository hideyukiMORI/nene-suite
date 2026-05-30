export const suiteAuditEventKeys = {
  all: ['suite-audit-events'] as const,
  list: () => [...suiteAuditEventKeys.all, 'list'] as const,
}
