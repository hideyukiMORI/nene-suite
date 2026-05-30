import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <main>
      <h1>{t('common.error.notFound')}</h1>
      <Link to="/">{t('suite.nav.home')}</Link>
    </main>
  )
}
