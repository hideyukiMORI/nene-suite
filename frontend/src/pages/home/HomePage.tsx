import { AppLauncher } from '@/features/app-launcher'
import { useTranslation } from '@/shared/i18n'
import { PageHeader } from '@/shared/ui'

/** Apex launcher. Content-only — global chrome (header/nav/account) is in AppShell. */
export function HomePage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader title={t('suite.launcher.title')} />
      <p>{t('suite.launcher.description')}</p>
      <AppLauncher />
    </>
  )
}
