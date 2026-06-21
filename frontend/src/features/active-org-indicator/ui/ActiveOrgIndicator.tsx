import { authStore } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'

/**
 * Read-only badge of the signed-in operator's active-org context (A6): platform
 * superadmin flag, active organization, and org role. Reads the persisted session
 * synchronously; a pre-A6 session degrades to "no active org".
 */
export function ActiveOrgIndicator() {
  const { t } = useTranslation()
  const isSuperadmin = authStore.isSuperadmin()
  const role = authStore.getRole()
  const orgExternalId = authStore.getOrgExternalId()

  return (
    <span>
      {isSuperadmin ? <small>{t('suite.org.indicator.superadmin')}</small> : null}
      {orgExternalId !== null ? (
        <small>{t('suite.org.indicator.activeOrg', { org: orgExternalId })}</small>
      ) : (
        <small>{t('suite.org.indicator.noOrg')}</small>
      )}
      {role !== null ? <small>{t('suite.org.indicator.role', { role })}</small> : null}
    </span>
  )
}
