import { useState } from 'react'
import { authStore, useSessionOrganizations, useSwitchActiveOrganization } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'

/**
 * Active-org context (A6) with an organization switcher (§7 ③). Shows the platform superadmin
 * flag and the org role from the persisted session, and — when the operator belongs to one or
 * more organizations — a dropdown that re-scopes the session to the chosen org. A switch re-issues
 * the JWT server-side; on success the new session is persisted and cached queries are invalidated.
 */
export function ActiveOrgIndicator() {
  const { t } = useTranslation()
  const isSuperadmin = authStore.isSuperadmin()
  const role = authStore.getRole()
  const activeExternalId = authStore.getOrgExternalId()

  const organizationsQuery = useSessionOrganizations()
  const switchOrganization = useSwitchActiveOrganization()
  const organizations = organizationsQuery.data ?? []

  const activeOrganizationId =
    organizations.find((organization) => organization.externalId === activeExternalId)
      ?.organizationId ?? ''
  const [pendingId, setPendingId] = useState<string | null>(null)
  const selectedId = pendingId ?? activeOrganizationId

  const onSelect = (organizationId: string): void => {
    if (organizationId === '' || organizationId === activeOrganizationId) {
      return
    }
    setPendingId(organizationId)
    switchOrganization.mutate(
      { organizationId },
      {
        onError: () => {
          setPendingId(null)
        },
      },
    )
  }

  return (
    <span>
      {isSuperadmin ? <small>{t('suite.org.indicator.superadmin')}</small> : null}
      {organizationsQuery.isLoading ? null : organizations.length > 0 ? (
        <label>
          <small>{t('suite.org.switcher.label')}</small>
          <select
            value={selectedId}
            disabled={switchOrganization.isPending}
            onChange={(event) => {
              onSelect(event.target.value)
            }}
          >
            <option value="" disabled>
              {t('suite.org.switcher.placeholder')}
            </option>
            {organizations.map((organization) => (
              <option key={organization.organizationId} value={organization.organizationId}>
                {organization.name}
              </option>
            ))}
          </select>
        </label>
      ) : (
        <small>{t('suite.org.indicator.noOrg')}</small>
      )}
      {role !== null ? <small>{t('suite.org.indicator.role', { role })}</small> : null}
    </span>
  )
}
