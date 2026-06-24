import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { authStore, useSignOut } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './AccountMenu.module.css'

function initials(email: string): string {
  const name = email.split('@')[0] ?? email
  return (name.slice(0, 2) || '··').toUpperCase()
}

/** Account popover: identity + profile/security links + logout (DESIGN-SYSTEM §5.5). */
export function AccountMenu() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const signOut = useSignOut()
  const [open, setOpen] = useState(false)

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
            : null

  const goAccount = (): void => {
    setOpen(false)
    void navigate('/account')
  }

  const handleSignOut = (): void => {
    setOpen(false)
    signOut.mutate(undefined, {
      onSettled: () => {
        void navigate('/login', { replace: true })
      },
    })
  }

  return (
    <div className={styles['root']}>
      <button
        type="button"
        className={styles['avatar']}
        aria-label={t('suite.shell.account.menu')}
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => {
          setOpen((value) => !value)
        }}
      >
        {initials(email)}
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
            <div className={styles['identity']}>
              <span className={styles['avatarLg']}>{initials(email)}</span>
              <span className={styles['idText']}>
                <span className={styles['email']}>{email}</span>
                {roleLabel !== null ? <span className={styles['role']}>{roleLabel}</span> : null}
              </span>
            </div>
            <div className={styles['sep']} />
            <button type="button" role="menuitem" className={styles['item']} onClick={goAccount}>
              <Icon name="person" size={19} color="var(--fg-3)" />
              {t('suite.shell.account.profile')}
            </button>
            <button type="button" role="menuitem" className={styles['item']} onClick={goAccount}>
              <Icon name="shield" size={19} color="var(--fg-3)" />
              {t('suite.shell.account.security')}
            </button>
            <button
              type="button"
              role="menuitem"
              className={styles['itemDanger']}
              disabled={signOut.isPending}
              onClick={handleSignOut}
            >
              <Icon name="logout" size={19} />
              {t('suite.nav.logout')}
            </button>
          </div>
        </>
      ) : null}
    </div>
  )
}
