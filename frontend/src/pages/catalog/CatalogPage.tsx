import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { PlaceholderState } from '@/shared/ui'

/** Catalog store — built in a later phase. Honest placeholder, no fabricated data. */
export function CatalogPage() {
  const { t } = useTranslation()
  return (
    <PlaceholderState
      icon="apps"
      title={t('suite.catalog.title')}
      description={t('suite.catalog.placeholder')}
      action={<Link to="/install">{t('suite.nav.getApps')}</Link>}
    />
  )
}
