import { useEffect, useRef, useState, type KeyboardEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { authStore, useSignOut } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './AccountMenu.module.css'

function initials(email: string): string {
  const name = email.split('@')[0] ?? email
  return (name.slice(0, 2) || '··').toUpperCase()
}

/**
 * Account popover: identity + profile/security links + logout (DESIGN-SYSTEM
 * §5.5). Keyboard follows the WAI-ARIA APG menu-button pattern: opening moves
 * focus to the first item, arrows cycle through items, and Escape/Tab close
 * the menu returning focus to the trigger.
 */
export function AccountMenu() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const signOut = useSignOut()
  const [open, setOpen] = useState(false)
  const triggerRef = useRef<HTMLButtonElement>(null)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return
    menuRef.current?.querySelector<HTMLElement>('[role="menuitem"]:not([disabled])')?.focus()
  }, [open])

  const closeAndRestore = (): void => {
    setOpen(false)
    triggerRef.current?.focus()
  }

  const onMenuKeyDown = (event: KeyboardEvent<HTMLDivElement>): void => {
    if (event.key === 'Escape' || event.key === 'Tab') {
      event.preventDefault()
      closeAndRestore()
      return
    }
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return
    event.preventDefault()
    const items = Array.from(
      menuRef.current?.querySelectorAll<HTMLElement>('[role="menuitem"]:not([disabled])') ?? [],
    )
    if (items.length === 0) return
    const index = items.findIndex((item) => item === document.activeElement)
    const delta = event.key === 'ArrowDown' ? 1 : -1
    items[(index + delta + items.length) % items.length]?.focus()
  }

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
        ref={triggerRef}
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
            tabIndex={-1}
            onClick={() => {
              setOpen(false)
            }}
          />
          <div
            ref={menuRef}
            className={styles['menu']}
            role="menu"
            aria-label={t('suite.shell.account.menu')}
            tabIndex={-1}
            onKeyDown={onMenuKeyDown}
          >
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
