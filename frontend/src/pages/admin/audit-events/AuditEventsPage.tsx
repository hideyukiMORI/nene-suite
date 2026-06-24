import { AuditViewer } from '@/features/audit-viewer'

/** Content-only — the global chrome is owned by AppShell; AuditViewer owns its header. */
export function AuditEventsPage() {
  return <AuditViewer />
}
