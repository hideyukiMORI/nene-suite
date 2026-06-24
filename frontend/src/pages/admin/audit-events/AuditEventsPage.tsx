import { AuditEventsTable } from '@/features/audit-viewer'
import { useTranslation } from '@/shared/i18n'
import { PageHeader } from '@/shared/ui'

/** Content-only — the global chrome (header/nav) is owned by AppShell. */
export function AuditEventsPage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader title={t('suite.audit.title')} />
      <p>{t('suite.audit.description')}</p>
      <AuditEventsTable />
    </>
  )
}
