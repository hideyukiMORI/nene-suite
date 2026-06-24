import { useTranslation } from '@/shared/i18n'
import { PlaceholderState } from '@/shared/ui'

/** Password / MFA — backend not yet exposed (Phase B). Honest placeholder. */
export function SecurityTab() {
  const { t } = useTranslation()
  return (
    <PlaceholderState
      icon="shield"
      title={t('suite.account.security.title')}
      description={t('suite.account.security.phaseB')}
    />
  )
}
