import { useTranslation } from '@/shared/i18n'
import { PlaceholderState } from '@/shared/ui'

/** Settings — built in a later phase. Honest placeholder, no fabricated data. */
export function SettingsPage() {
  const { t } = useTranslation()
  return (
    <PlaceholderState
      icon="settings"
      title={t('suite.settings.title')}
      description={t('suite.settings.placeholder')}
    />
  )
}
