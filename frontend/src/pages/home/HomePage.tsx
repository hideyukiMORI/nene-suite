import { Link, useNavigate } from 'react-router-dom'
import { authStore, useSignOut } from '@/entities/auth'
import { ActiveOrgIndicator } from '@/features/active-org-indicator'
import { AppLauncher } from '@/features/app-launcher'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

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
      <PageHeader
        title={t('suite.nav.appTitle')}
        actions={
          <>
            <Link to="/install">{t('suite.nav.install')}</Link>
            <Link to="/admin/audit-events">{t('suite.nav.audit')}</Link>
            {authStore.isSuperadmin() ? (
              <Link to="/admin/organizations">{t('suite.nav.organizations')}</Link>
            ) : null}
            <ActiveOrgIndicator />
            <LocaleSwitcher />
            <button type="button" onClick={handleSignOut} disabled={signOut.isPending}>
              {t('suite.nav.logout')}
            </button>
          </>
        }
      />
      <section>
        <h2>{t('suite.launcher.title')}</h2>
        <p>{t('suite.launcher.description')}</p>
        <AppLauncher />
      </section>
    </main>
  )
}
