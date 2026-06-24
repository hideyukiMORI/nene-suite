import { useNavigate } from 'react-router-dom'
import { authStore, useSessionOrganizations, useSignOut } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './account.module.css'

/**
 * Current session (real: email / active org / expiry) + log out of this device
 * (real). Per-device session listing and "log out everywhere" are Phase B.
 */
export function SessionsTab() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const signOut = useSignOut()
  const session = authStore.getSession()
  const organizations = useSessionOrganizations().data ?? []
  const activeOrg = organizations.find((org) => org.externalId === session?.orgExternalId)

  const handleSignOut = (): void => {
    signOut.mutate(undefined, {
      onSettled: () => {
        void navigate('/login', { replace: true })
      },
    })
  }

  return (
    <div className={styles['card']}>
      <h3 className={styles['cardTitle']}>{t('suite.account.sessions.current')}</h3>
      <div className={styles['fieldRow']}>
        <span className={styles['fieldLabel']}>{t('suite.account.field.email')}</span>
        <span className={styles['fieldValue']}>{session?.operator.email ?? '—'}</span>
      </div>
      <div className={styles['fieldRow']}>
        <span className={styles['fieldLabel']}>{t('suite.account.sessions.activeOrg')}</span>
        <span className={styles['fieldValue']}>
          {activeOrg?.name ?? t('suite.account.sessions.none')}
        </span>
      </div>
      <div className={styles['fieldRow']}>
        <span className={styles['fieldLabel']}>{t('suite.account.sessions.expires')}</span>
        <span className={styles['mono']}>{session?.expiresAt ?? '—'}</span>
      </div>
      <button
        type="button"
        className={styles['logoutBtn']}
        disabled={signOut.isPending}
        onClick={handleSignOut}
      >
        <Icon name="logout" size={18} />
        {t('suite.nav.logout')}
      </button>
      <p className={styles['phaseB']}>{t('suite.account.sessions.phaseB')}</p>
    </div>
  )
}
