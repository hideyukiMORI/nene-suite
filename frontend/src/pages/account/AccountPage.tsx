import { useTranslation } from '@/shared/i18n'
import { PlaceholderState } from '@/shared/ui'

/** Account & security — built in a later phase. Honest placeholder, no fabricated data. */
export function AccountPage() {
  const { t } = useTranslation()
  return (
    <PlaceholderState
      icon="manage_accounts"
      title={t('suite.account.title')}
      description={t('suite.account.placeholder')}
    />
  )
}
