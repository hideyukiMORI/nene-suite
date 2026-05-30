import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function CompleteStep() {
  const { t } = useTranslation()

  return (
    <section>
      <h3>{t('suite.install.complete.title')}</h3>
      <p>{t('suite.install.complete.description')}</p>
      <Link to="/">{t('suite.install.complete.goToLauncher')}</Link>
    </section>
  )
}
