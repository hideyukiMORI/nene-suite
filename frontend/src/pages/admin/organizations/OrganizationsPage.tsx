import { AdminHub } from '@/features/admin-hub'

/** Content-only — the global chrome is owned by AppShell; AdminHub owns its header + tabs. */
export function OrganizationsPage() {
  return <AdminHub />
}
