import { Link } from 'react-router-dom'
import { InstallWizard } from '@/features/install-wizard'
import { useTranslation } from '@/shared/i18n'

export function InstallPage() {
  const { t } = useTranslation()

  return (
    <main>
      <header>
        <h1>{t('suite.nav.appTitle')}</h1>
        <Link to="/">{t('suite.nav.home')}</Link>
      </header>
      <InstallWizard />
    </main>
  )
}
