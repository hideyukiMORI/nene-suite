import { Link, NavLink } from 'react-router-dom'
import { ActiveOrgIndicator } from '@/features/active-org-indicator'
import { env } from '@/shared/config/env'
import { useTranslation } from '@/shared/i18n'
import { Icon, SuiteMark, ThemeToggle } from '@/shared/ui'
import { useAppNav } from '../hooks/use-app-nav'
import { AccountMenu } from './AccountMenu'
import styles from './AppHeader.module.css'
import { LocaleToggle } from './LocaleToggle'
import { NotificationsMenu } from './NotificationsMenu'

/** Fixed glass header — brand, primary nav, ⌘K trigger, and the right cluster. */
export function AppHeader({ onOpenPalette }: { onOpenPalette: () => void }) {
  const { t } = useTranslation()
  const nav = useAppNav()

  return (
    <header className={styles['header']}>
      <Link to="/" className={styles['brand']} aria-label={t('suite.nav.appTitle')}>
        <SuiteMark size={28} />
        <span className={styles['brandName']}>NeNe Suite</span>
        <span className={styles['edition']}>{env.edition.toUpperCase()}</span>
      </Link>

      <nav className={styles['nav']} aria-label={t('suite.shell.navLabel')}>
        {nav.map((item) => (
          <NavLink
            key={item.id}
            to={item.path}
            end={item.path === '/'}
            className={styles['navItem'] ?? ''}
          >
            <Icon name={item.icon} size={19} />
            <span>{item.label}</span>
          </NavLink>
        ))}
      </nav>

      <div className={styles['search']}>
        <button
          type="button"
          className={styles['searchBtn']}
          aria-label={t('suite.shell.commandPalette')}
          onClick={onOpenPalette}
        >
          <Icon name="search" size={19} color="var(--fg-3)" />
          <span className={styles['searchLabel']}>{t('suite.shell.search')}</span>
          <span className={styles['kbd']}>⌘K</span>
        </button>
      </div>

      <div className={styles['cluster']}>
        <ActiveOrgIndicator />
        <NotificationsMenu />
        <LocaleToggle />
        <ThemeToggle label={t('suite.theme.toggle')} />
        <AccountMenu />
      </div>
    </header>
  )
}
