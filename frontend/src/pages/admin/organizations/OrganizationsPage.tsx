import { OrganizationConsole } from '@/features/organization-console'
import { useTranslation } from '@/shared/i18n'
import { PageHeader } from '@/shared/ui'

/** Content-only — the global chrome (header/nav/org switcher) is owned by AppShell. */
export function OrganizationsPage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader title={t('suite.org.title')} />
      <p>{t('suite.org.description')}</p>
      <OrganizationConsole />
    </>
  )
}
