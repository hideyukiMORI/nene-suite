import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { getGuide } from '../content'
import { Callout } from './Callout'
import { HelpText } from './HelpText'
import styles from './help-guide.module.css'

export function HelpGuide({ slug }: { readonly slug: string }): ReactNode {
  const guide = getGuide(slug)

  if (guide === undefined) {
    return (
      <div className={styles['root']}>
        <h1 className={styles['title']}>ページが見つかりません</h1>
        <p className={styles['muted']}>
          このヘルプは存在しないか、移動した可能性があります。{' '}
          <Link to="/help" className={styles['inlineLink']}>
            ヘルプの入口へ戻る
          </Link>
        </p>
      </div>
    )
  }

  return (
    <article className={styles['root']}>
      <h1 className={styles['title']}>{guide.title}</h1>
      <p className={styles['summary']}>{guide.summary}</p>

      {guide.sections.map((section) => (
        <section key={section.heading} className={styles['section']}>
          <h2 className={styles['heading']}>{section.heading}</h2>

          {section.paragraphs?.map((paragraph) => (
            <p key={paragraph} className={styles['paragraph']}>
              <HelpText text={paragraph} />
            </p>
          ))}

          {section.steps ? (
            <ol className={styles['steps']}>
              {section.steps.map((step) => (
                <li key={step}>
                  <HelpText text={step} />
                </li>
              ))}
            </ol>
          ) : null}

          {section.bullets ? (
            <ul className={styles['bullets']}>
              {section.bullets.map((bullet) => (
                <li key={bullet}>
                  <HelpText text={bullet} />
                </li>
              ))}
            </ul>
          ) : null}

          {section.table ? (
            <div className={styles['tableWrap']}>
              <table className={styles['table']}>
                <thead>
                  <tr>
                    {section.table.headers.map((header) => (
                      <th key={header}>{header}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {section.table.rows.map((row) => (
                    <tr key={row.join('|')}>
                      {row.map((cell) => (
                        <td key={cell}>{cell}</td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}

          {section.notes?.map((note) => (
            <Callout key={note.text} tone={note.tone}>
              <HelpText text={note.text} />
            </Callout>
          ))}
        </section>
      ))}

      {guide.related && guide.related.length > 0 ? (
        <footer className={styles['related']}>
          <span className={styles['relatedLabel']}>関連</span>
          <div className={styles['relatedLinks']}>
            {guide.related.map((slugRef) => {
              const ref = getGuide(slugRef)
              if (ref === undefined) return null
              return (
                <Link key={slugRef} to={`/help/${slugRef}`} className={styles['relatedLink']}>
                  {ref.title}
                </Link>
              )
            })}
          </div>
        </footer>
      ) : null}
    </article>
  )
}
