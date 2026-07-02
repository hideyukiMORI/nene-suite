import type { ReactNode } from 'react'
import { NavLink } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { GUIDE_GROUP_LABEL_KEY, GUIDE_GROUP_ORDER, GUIDES } from '../content'
import { Callout } from './Callout'
import styles from './help-layout.module.css'

function linkClass({ isActive }: { isActive: boolean }): string {
  return isActive ? `${styles['link'] ?? ''} ${styles['linkActive'] ?? ''}` : (styles['link'] ?? '')
}

/**
 * Two-column help shell: a sticky table of contents + the routed content. The
 * help body is Japanese-first (ADR 0024), so every non-ja locale gets an
 * explicit "English version in preparation" notice instead of looking broken.
 */
export function HelpLayout({ children }: { readonly children: ReactNode }): ReactNode {
  const { t, locale } = useTranslation()

  return (
    <div className={styles['root']}>
      <aside className={styles['sidebar']} aria-label={t('suite.help.tocLabel')}>
        <NavLink to="/help" end className={linkClass}>
          {t('suite.help.homeLink')}
        </NavLink>
        {GUIDE_GROUP_ORDER.map((group) => {
          const guides = GUIDES.filter((guide) => guide.group === group)
          if (guides.length === 0) return null
          return (
            <div key={group} className={styles['group']}>
              <h2 className={styles['groupTitle']}>{t(GUIDE_GROUP_LABEL_KEY[group])}</h2>
              {guides.map((guide) => (
                <NavLink key={guide.slug} to={`/help/${guide.slug}`} className={linkClass}>
                  {guide.title}
                </NavLink>
              ))}
            </div>
          )
        })}
        <div className={styles['group']}>
          <h2 className={styles['groupTitle']}>{t('suite.help.termsGroup')}</h2>
          <NavLink to="/help/glossary" className={linkClass}>
            {t('suite.help.glossary.title')}
          </NavLink>
        </div>
      </aside>
      <div className={styles['content']}>
        {locale !== 'ja' ? <Callout tone="neutral">{t('suite.help.bodyNotice')}</Callout> : null}
        {children}
      </div>
    </div>
  )
}
