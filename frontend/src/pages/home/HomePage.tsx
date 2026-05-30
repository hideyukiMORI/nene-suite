import { useNavigate } from 'react-router-dom'
import { useSignOut } from '@/entities/auth'
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
        <button type="button" onClick={handleSignOut} disabled={signOut.isPending}>
          {t('suite.nav.logout')}
        </button>
      </header>
      <h2>{t('suite.launcher.title')}</h2>
      <p>{t('suite.launcher.description')}</p>
    </main>
  )
}
