import { Link } from 'react-router-dom'
import { AuditEventsTable } from '@/features/audit-viewer'
import { useTranslation } from '@/shared/i18n'

export function AuditEventsPage() {
  const { t } = useTranslation()

  return (
    <main>
      <header>
        <h1>{t('suite.audit.title')}</h1>
        <Link to="/">{t('suite.nav.home')}</Link>
      </header>
      <p>{t('suite.audit.description')}</p>
      <AuditEventsTable />
    </main>
  )
}
