import { SignInForm } from '@/features/sign-in'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

export function LoginPage() {
  const { t } = useTranslation()

  return (
    <main>
      <PageHeader title={t('suite.nav.appTitle')} actions={<LocaleSwitcher />} />
      <SignInForm />
    </main>
  )
}
