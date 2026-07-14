import { useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { GUIDE_GROUP_LABEL_KEY, GUIDE_GROUP_ORDER, GUIDES, searchHelp } from '../content'
import styles from './help-home.module.css'

/** "Find by what you want to do" shortcuts for beginners. */
const TASKS: readonly { readonly labelKey: MessageKey; readonly to: string }[] = [
  { labelKey: 'suite.help.task.install', to: '/help/install-wizard' },
  { labelKey: 'suite.help.task.roles', to: '/help/roles' },
  { labelKey: 'suite.help.task.safety', to: '/help/safety' },
  { labelKey: 'suite.help.task.faq', to: '/help/faq' },
]

export function HelpHome(): ReactNode {
  const { t } = useTranslation()
  const [query, setQuery] = useState('')
  const { guides, terms } = searchHelp(query)
  const searching = query.trim() !== ''

  return (
    <div className={styles['root']}>
      <header className={styles['header']}>
        <h1 className={styles['title']}>{t('suite.help.title')}</h1>
        <p className={styles['subtitle']}>{t('suite.help.subtitle')}</p>
      </header>

      <div className={styles['searchWrap']}>
        <Icon
          name="search"
          size={19}
          color="var(--color-text-faint)"
          className={styles['searchIcon'] ?? ''}
        />
        <input
          type="search"
          className={styles['search']}
          placeholder={t('suite.help.searchPlaceholder')}
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
          }}
          aria-label={t('suite.help.searchPlaceholder')}
        />
      </div>

      {searching ? (
        <section className={styles['section']}>
          <h2 className={styles['sectionTitle']}>{t('suite.help.resultsTitle')}</h2>
          {guides.length === 0 && terms.length === 0 ? (
            <p className={styles['muted']}>{t('suite.help.noResults')}</p>
          ) : (
            <div className={styles['results']}>
              {guides.map((guide) => (
                <Link key={guide.slug} to={`/help/${guide.slug}`} className={styles['result']}>
                  <Icon name="article" size={17} color="var(--color-text-faint)" />
                  <span>{guide.title}</span>
                </Link>
              ))}
              {terms.map((term) => (
                <Link
                  key={term.id}
                  to={`/help/glossary#gt-${term.id}`}
                  className={styles['result']}
                >
                  <Icon name="label" size={17} color="var(--color-text-faint)" />
                  <span>{term.term}</span>
                </Link>
              ))}
            </div>
          )}
        </section>
      ) : (
        <>
          <section className={styles['section']}>
            <h2 className={styles['sectionTitle']}>{t('suite.help.tasksTitle')}</h2>
            <div className={styles['tasks']}>
              {TASKS.map((task) => (
                <Link key={task.to} to={task.to} className={styles['task']}>
                  <Icon name="arrow_forward" size={18} color="var(--color-x-brand)" />
                  {t(task.labelKey)}
                </Link>
              ))}
            </div>
          </section>

          {GUIDE_GROUP_ORDER.map((group) => {
            const groupGuides = GUIDES.filter((guide) => guide.group === group)
            if (groupGuides.length === 0) return null
            return (
              <section key={group} className={styles['section']}>
                <h2 className={styles['sectionTitle']}>{t(GUIDE_GROUP_LABEL_KEY[group])}</h2>
                <div className={styles['grid']}>
                  {groupGuides.map((guide) => (
                    <Link key={guide.slug} to={`/help/${guide.slug}`} className={styles['card']}>
                      <span className={styles['cardTitle']}>{guide.title}</span>
                      <span className={styles['cardSummary']}>{guide.summary}</span>
                    </Link>
                  ))}
                </div>
              </section>
            )
          })}

          <Link to="/help/glossary" className={styles['glossaryCta']}>
            <Icon name="menu_book" size={20} color="var(--color-x-brand)" />
            {t('suite.help.glossaryCta')}
          </Link>
        </>
      )}
    </div>
  )
}
