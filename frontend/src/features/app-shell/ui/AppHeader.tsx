import { ActiveOrgIndicator } from '@/features/active-org-indicator'
import { useTranslation } from '@/shared/i18n'
import { Icon, ThemeToggle } from '@/shared/ui'
import { AccountMenu } from './AccountMenu'
import styles from './AppHeader.module.css'
import { LocaleToggle } from './LocaleToggle'
import { NotificationsMenu } from './NotificationsMenu'

interface AppHeaderProps {
  readonly onOpenPalette: () => void
  /** Opens the off-canvas sidebar drawer (≤680px hamburger). */
  readonly onOpenMenu: () => void
}

/** Slim top bar of the main column — drawer toggle, ⌘K search, and the right cluster. */
export function AppHeader({ onOpenPalette, onOpenMenu }: AppHeaderProps) {
  const { t } = useTranslation()

  return (
    <header className={styles['header']}>
      <button
        type="button"
        className={styles['menuBtn']}
        aria-label={t('suite.nav.openMenu')}
        onClick={onOpenMenu}
      >
        <Icon name="menu" size={22} />
      </button>

      <button
        type="button"
        className={styles['searchBtn']}
        aria-label={t('suite.shell.commandPalette')}
        onClick={onOpenPalette}
      >
        <Icon name="search" size={19} color="var(--color-text-faint)" />
        <span className={styles['searchLabel']}>{t('suite.shell.search')}</span>
        <span className={styles['kbd']}>⌘K</span>
      </button>

      <div className={styles['spacer']} />

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
