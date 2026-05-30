import { SignInForm } from '@/features/sign-in'
import { useTranslation } from '@/shared/i18n'

export function LoginPage() {
  const { t } = useTranslation()

  return (
    <main>
      <h1>{t('suite.nav.appTitle')}</h1>
      <SignInForm />
    </main>
  )
}
