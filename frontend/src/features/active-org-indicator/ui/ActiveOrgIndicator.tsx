import { useState } from 'react'
import {
  authStore,
  useSessionOrganizations,
  useSwitchActiveOrganization,
  type SessionOrganization,
} from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './ActiveOrgIndicator.module.css'

/**
 * Active-org switcher (A6 / §7 ③) styled as the header org control (DESIGN-SYSTEM
 * §5.5). Lists the operator's organizations and re-scopes the session to the
 * chosen one — the switch re-issues the JWT server-side, persists the new
 * session, and invalidates cached queries. Hidden when the operator has no
 * org-scoped membership (e.g. a superadmin with none) so the chrome stays clean.
 */
export function ActiveOrgIndicator() {
  const { t } = useTranslation()
  const activeExternalId = authStore.getOrgExternalId()

  const organizationsQuery = useSessionOrganizations()
  const switchOrganization = useSwitchActiveOrganization()
  const organizations = organizationsQuery.data ?? []

  const [open, setOpen] = useState(false)

  if (organizationsQuery.isLoading || organizations.length === 0) {
    return null
  }

  const activeOrg = organizations.find((org) => org.externalId === activeExternalId) ?? null
  const activeLabel = activeOrg?.name ?? t('suite.org.switcher.placeholder')
  const initial = (activeOrg?.slug ?? activeOrg?.name ?? '?').slice(0, 1).toUpperCase()

  const onSelect = (org: SessionOrganization): void => {
    setOpen(false)
    if (org.externalId === activeExternalId) return
    switchOrganization.mutate({ organizationId: org.organizationId })
  }

  return (
    <div className={styles['root']}>
      <button
        type="button"
        className={styles['trigger']}
        aria-haspopup="menu"
        aria-expanded={open}
        disabled={switchOrganization.isPending}
        onClick={() => {
          setOpen((value) => !value)
        }}
      >
        <span className={styles['avatar']}>{initial}</span>
        <span className={styles['name']}>{activeLabel}</span>
        <Icon name="unfold_more" size={18} color="var(--fg-3)" />
      </button>
      {open ? (
        <>
          <button
            type="button"
            className={styles['scrim']}
            aria-label={t('common.actions.close')}
            onClick={() => {
              setOpen(false)
            }}
          />
          <div className={styles['menu']} role="menu">
            <div className={styles['header']}>{t('suite.org.switcher.label')}</div>
            {organizations.map((org) => (
              <button
                key={org.organizationId}
                type="button"
                role="menuitem"
                className={styles['item']}
                onClick={() => {
                  onSelect(org)
                }}
              >
                <span className={styles['itemAvatar']}>{org.slug.slice(0, 2).toUpperCase()}</span>
                <span className={styles['itemText']}>
                  <span className={styles['itemName']}>{org.name}</span>
                  <span className={styles['itemMeta']}>{org.slug}</span>
                </span>
                {org.externalId === activeExternalId ? (
                  <Icon name="check" size={19} color="var(--accent)" />
                ) : null}
              </button>
            ))}
          </div>
        </>
      ) : null}
    </div>
  )
}
