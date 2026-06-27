import type { ReactNode } from 'react'
import { NavLink } from 'react-router-dom'
import { GUIDE_GROUP_ORDER, GUIDE_GROUPS, GUIDES } from '../content'
import styles from './help-layout.module.css'

function linkClass({ isActive }: { isActive: boolean }): string {
  return isActive ? `${styles['link'] ?? ''} ${styles['linkActive'] ?? ''}` : (styles['link'] ?? '')
}

/** Two-column help shell: a sticky table of contents + the routed content. */
export function HelpLayout({ children }: { readonly children: ReactNode }): ReactNode {
  return (
    <div className={styles['root']}>
      <aside className={styles['sidebar']} aria-label="ヘルプの目次">
        <NavLink to="/help" end className={linkClass}>
          ヘルプの入口
        </NavLink>
        {GUIDE_GROUP_ORDER.map((group) => {
          const guides = GUIDES.filter((guide) => guide.group === group)
          if (guides.length === 0) return null
          return (
            <div key={group} className={styles['group']}>
              <h2 className={styles['groupTitle']}>{GUIDE_GROUPS[group]}</h2>
              {guides.map((guide) => (
                <NavLink key={guide.slug} to={`/help/${guide.slug}`} className={linkClass}>
                  {guide.title}
                </NavLink>
              ))}
            </div>
          )
        })}
        <div className={styles['group']}>
          <h2 className={styles['groupTitle']}>用語</h2>
          <NavLink to="/help/glossary" className={linkClass}>
            用語集
          </NavLink>
        </div>
      </aside>
      <div className={styles['content']}>{children}</div>
    </div>
  )
}
