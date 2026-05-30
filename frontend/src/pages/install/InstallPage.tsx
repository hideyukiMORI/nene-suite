import { Link } from 'react-router-dom'
import { InstallWizard } from '@/features/install-wizard'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

export function InstallPage() {
  const { t } = useTranslation()

  return (
    <main>
      <PageHeader
        title={t('suite.nav.appTitle')}
        actions={
          <>
            <Link to="/">{t('suite.nav.home')}</Link>
            <LocaleSwitcher />
          </>
        }
      />
      <InstallWizard />
    </main>
  )
}
