import { NavLink } from 'react-router-dom'
import { useCurrentOperator } from '@/entities/auth'
import { env } from '@/shared/config/env'
import { useTranslation } from '@/shared/i18n'
import { Icon, SuiteMark } from '@/shared/ui'
import { useAppNavGroups } from '../hooks/use-app-nav'
import styles from './AppSidebar.module.css'

interface AppSidebarProps {
  /** Drawer state — only meaningful at the ≤680px breakpoint (off-canvas). */
  readonly open: boolean
  /** Called when the drawer should close (overlay, Escape, or nav). */
  readonly onClose: () => void
}

/** Left navigation rail: brand, grouped IA, and an identity footer. Collapses to an
 * icon rail (≤1000px) and an off-canvas drawer (≤680px). */
export function AppSidebar({ open, onClose }: AppSidebarProps) {
  const { t } = useTranslation()
  const groups = useAppNavGroups()
  const { data: operator } = useCurrentOperator()

  const acctName = operator?.displayName ?? operator?.email ?? '—'
  const initial = acctName.slice(0, 1).toUpperCase()

  return (
    <aside
      className={`${styles['sidebar'] ?? ''} ${open ? (styles['open'] ?? '') : ''}`}
      aria-label={t('suite.shell.navLabel')}
    >
      <div className={styles['brand']}>
        <SuiteMark size={30} />
        <span className={styles['brandText']}>NeNe Suite</span>
        <span className={styles['edition']}>{env.edition.toUpperCase()}</span>
      </div>

      <nav className={styles['nav']}>
        {groups.map((group) => (
          <div key={group.id} className={styles['group']}>
            <p className={styles['groupHeading']}>
              <span>{group.title}</span>
            </p>
            {group.items.map((item) => (
              <NavLink
                key={item.id}
                to={item.path}
                end={item.path === '/'}
                title={item.label}
                onClick={onClose}
                className={({ isActive }) =>
                  `${styles['item'] ?? ''} ${isActive ? (styles['itemActive'] ?? '') : ''}`
                }
              >
                <Icon name={item.icon} size={21} />
                <span className={styles['label']}>{item.label}</span>
              </NavLink>
            ))}
          </div>
        ))}
      </nav>

      <div className={styles['foot']}>
        <span className={styles['avatar']} aria-hidden="true">
          {initial}
        </span>
        <span className={styles['footText']}>
          <span className={styles['acctName']}>{acctName}</span>
          <span className={styles['version']}>{env.edition}</span>
        </span>
      </div>
    </aside>
  )
}
