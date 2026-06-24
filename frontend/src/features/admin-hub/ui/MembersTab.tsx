import { authStore, useSessionOrganizations } from '@/entities/auth'
import { MembershipConsole } from '@/features/membership-console'
import { useTranslation } from '@/shared/i18n'
import styles from './admin-hub.module.css'

/** Members of the active organization. No active org → guide to the Organizations tab. */
export function MembersTab() {
  const { t } = useTranslation()
  const organizations = useSessionOrganizations().data ?? []
  const activeExternalId = authStore.getOrgExternalId()
  const activeOrg = organizations.find((org) => org.externalId === activeExternalId)

  if (activeOrg === undefined) {
    return <p className={styles['muted']}>{t('suite.admin.members.selectOrg')}</p>
  }

  return <MembershipConsole organizationId={activeOrg.organizationId} />
}
