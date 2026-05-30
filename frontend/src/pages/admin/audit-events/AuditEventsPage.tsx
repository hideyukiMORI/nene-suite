import { Link } from 'react-router-dom'
import { AuditEventsTable } from '@/features/audit-viewer'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

export function AuditEventsPage() {
  const { t } = useTranslation()

  return (
    <main>
      <PageHeader
        title={t('suite.audit.title')}
        actions={
          <>
            <Link to="/">{t('suite.nav.home')}</Link>
            <LocaleSwitcher />
          </>
        }
      />
      <p>{t('suite.audit.description')}</p>
      <AuditEventsTable />
    </main>
  )
}
