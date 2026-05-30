import { useRouteError } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function RootErrorBoundary() {
  const { t } = useTranslation()
  const error = useRouteError()

  if (import.meta.env.DEV) {
    console.error('[RootErrorBoundary]', error)
  }

  return (
    <div role="alert">
      <h1>{t('common.state.error')}</h1>
      <p>{t('common.error.unknown')}</p>
    </div>
  )
}
