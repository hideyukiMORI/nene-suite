import { Link } from 'react-router-dom'
import { ActiveOrgIndicator } from '@/features/active-org-indicator'
import { OrganizationConsole } from '@/features/organization-console'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

export function OrganizationsPage() {
  const { t } = useTranslation()

  return (
    <main>
      <PageHeader
        title={t('suite.org.title')}
        actions={
          <>
            <Link to="/">{t('suite.nav.home')}</Link>
            <ActiveOrgIndicator />
            <LocaleSwitcher />
          </>
        }
      />
      <p>{t('suite.org.description')}</p>
      <OrganizationConsole />
    </main>
  )
}
