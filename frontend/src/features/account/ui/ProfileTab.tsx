import { authStore } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import styles from './account.module.css'

function initials(email: string): string {
  const name = email.split('@')[0] ?? email
  return (name.slice(0, 2) || '··').toUpperCase()
}

/** Read-only identity from the persisted session. Editing is Phase B. */
export function ProfileTab() {
  const { t } = useTranslation()
  const session = authStore.getSession()
  const email = session?.operator.email ?? ''
  const roleLabel =
    session?.superadmin === true
      ? t('suite.org.indicator.superadmin')
      : session?.role === 'admin'
        ? t('suite.member.role.admin')
        : session?.role === 'member'
          ? t('suite.member.role.member')
          : session?.role === 'viewer'
            ? t('suite.member.role.viewer')
            : '—'

  return (
    <div className={styles['card']}>
      <div className={styles['identity']}>
        <span className={styles['avatarLg']}>{initials(email)}</span>
        <div className={styles['fieldGrid']}>
          <div className={styles['fieldRow']}>
            <span className={styles['fieldLabel']}>{t('suite.account.field.email')}</span>
            <span className={styles['fieldValue']}>{email}</span>
          </div>
          <div className={styles['fieldRow']}>
            <span className={styles['fieldLabel']}>{t('suite.account.field.role')}</span>
            <span className={styles['fieldValue']}>{roleLabel}</span>
          </div>
        </div>
      </div>
      <p className={styles['phaseB']}>{t('suite.account.profile.phaseB')}</p>
    </div>
  )
}
