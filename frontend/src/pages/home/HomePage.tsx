import { Link, useNavigate } from 'react-router-dom'
import { useSignOut } from '@/entities/auth'
import { AppLauncher } from '@/features/app-launcher'
import { useTranslation } from '@/shared/i18n'

export function HomePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const signOut = useSignOut()

  const handleSignOut = (): void => {
    signOut.mutate(undefined, {
      onSettled: () => {
        void navigate('/login', { replace: true })
      },
    })
  }

  return (
    <main>
      <header>
        <h1>{t('suite.nav.appTitle')}</h1>
        <nav>
          <Link to="/install">{t('suite.nav.install')}</Link>
          <Link to="/admin/audit-events">{t('suite.nav.audit')}</Link>
        </nav>
        <button type="button" onClick={handleSignOut} disabled={signOut.isPending}>
          {t('suite.nav.logout')}
        </button>
      </header>
      <section>
        <h2>{t('suite.launcher.title')}</h2>
        <p>{t('suite.launcher.description')}</p>
        <AppLauncher />
      </section>
    </main>
  )
}
