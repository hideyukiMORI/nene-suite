import { Fragment, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { IDENTIFIER_ANCHORS, IDENTIFIERS_BY_LENGTH } from '../content'
import styles from './help-text.module.css'

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

const PATTERN: RegExp | null =
  IDENTIFIERS_BY_LENGTH.length > 0
    ? new RegExp(`(${IDENTIFIERS_BY_LENGTH.map(escapeRegExp).join('|')})`, 'g')
    : null

/**
 * Renders plain help text, turning known identifiers (provision, manifest, …)
 * into monospace chips that link to their glossary entry. Keeps Japanese prose
 * as the readable body; the identifier stays inline and discoverable.
 */
export function HelpText({ text }: { readonly text: string }): ReactNode {
  if (PATTERN === null) return text
  const parts: ReactNode[] = []
  let cursor = 0
  for (const match of text.matchAll(PATTERN)) {
    const identifier = match[0]
    const index = match.index
    const anchor = IDENTIFIER_ANCHORS[identifier]
    if (anchor === undefined) continue
    if (index > cursor) {
      parts.push(<Fragment key={`pre-${String(index)}`}>{text.slice(cursor, index)}</Fragment>)
    }
    parts.push(
      <Link key={`id-${String(index)}`} to={`/help/glossary#${anchor}`} className={styles['term']}>
        {identifier}
      </Link>,
    )
    cursor = index + identifier.length
  }
  if (parts.length === 0) return text
  if (cursor < text.length) parts.push(<Fragment key="tail">{text.slice(cursor)}</Fragment>)
  return <>{parts}</>
}
