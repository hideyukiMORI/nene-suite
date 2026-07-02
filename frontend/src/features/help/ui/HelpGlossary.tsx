import { useEffect, useState, type ReactNode } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { GLOSSARY, GLOSSARY_CATEGORY_LABEL_KEY, searchHelp } from '../content'
import type { GlossaryCategory } from '../content'
import styles from './help-glossary.module.css'

const CATEGORY_ORDER: readonly GlossaryCategory[] = [
  'basics',
  'tenancy',
  'install',
  'database',
  'federation',
  'origin',
  'operation',
]

export function HelpGlossary(): ReactNode {
  const { t } = useTranslation()
  const [query, setQuery] = useState('')
  const { hash } = useLocation()
  const { terms } = searchHelp(query)
  const termsById = new Map(GLOSSARY.map((term) => [term.id, term]))

  useEffect(() => {
    if (hash === '') return
    const target = document.getElementById(hash.slice(1))
    if (target !== null) target.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, [hash])

  return (
    <div className={styles['root']}>
      <h1 className={styles['title']}>{t('suite.help.glossary.title')}</h1>
      <p className={styles['subtitle']}>{t('suite.help.glossary.subtitle')}</p>

      <div className={styles['searchWrap']}>
        <Icon name="search" size={19} color="var(--fg-3)" className={styles['searchIcon'] ?? ''} />
        <input
          type="search"
          className={styles['search']}
          placeholder={t('suite.help.glossary.searchPlaceholder')}
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
          }}
          aria-label={t('suite.help.glossary.searchLabel')}
        />
      </div>

      {terms.length === 0 ? (
        <p className={styles['muted']}>{t('suite.help.glossary.noResults')}</p>
      ) : null}

      {CATEGORY_ORDER.map((category) => {
        const inCategory = terms.filter((term) => term.category === category)
        if (inCategory.length === 0) return null
        return (
          <section key={category} className={styles['group']}>
            <h2 className={styles['groupTitle']}>{t(GLOSSARY_CATEGORY_LABEL_KEY[category])}</h2>
            {inCategory.map((term) => (
              <article key={term.id} id={`gt-${term.id}`} className={styles['term']}>
                <h3 className={styles['termName']}>{term.term}</h3>
                <p className={styles['short']}>{term.short}</p>
                {term.body?.map((paragraph) => (
                  <p key={paragraph} className={styles['body']}>
                    {paragraph}
                  </p>
                ))}
                {term.see && term.see.length > 0 ? (
                  <p className={styles['see']}>
                    {t('suite.help.related')}:{' '}
                    {term.see.map((id, position) => {
                      const ref = termsById.get(id)
                      if (ref === undefined) return null
                      return (
                        <span key={id}>
                          {position > 0 ? '、' : null}
                          <Link to={`/help/glossary#gt-${id}`} className={styles['seeLink']}>
                            {ref.term}
                          </Link>
                        </span>
                      )
                    })}
                  </p>
                ) : null}
              </article>
            ))}
          </section>
        )
      })}
    </div>
  )
}
