import { InstallWizard } from '@/features/install-wizard'
import { useTranslation } from '@/shared/i18n'
import { PageHeader } from '@/shared/ui'

/** Content-only — the global chrome (header/nav) is owned by AppShell. */
export function InstallPage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader title={t('suite.nav.install')} />
      <InstallWizard />
    </>
  )
}
