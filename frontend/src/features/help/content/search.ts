import { GLOSSARY } from './glossary'
import { GUIDES } from './guides'
import type { GlossaryTerm, Guide } from './types'

export interface HelpSearchResult {
  readonly guides: readonly Guide[]
  readonly terms: readonly GlossaryTerm[]
}

function guideHaystack(guide: Guide): string {
  const sectionText = guide.sections.flatMap((section) => [
    section.heading,
    ...(section.paragraphs ?? []),
    ...(section.bullets ?? []),
    ...(section.steps ?? []),
  ])
  return [guide.title, guide.summary, ...sectionText].join(' ').toLowerCase()
}

function termHaystack(term: GlossaryTerm): string {
  return [term.term, term.reading ?? '', term.short, ...(term.body ?? [])].join(' ').toLowerCase()
}

/**
 * Full-text help search across guides and glossary terms. An empty query
 * returns everything (so callers can render the full index).
 */
export function searchHelp(query: string): HelpSearchResult {
  const needle = query.trim().toLowerCase()
  if (needle === '') return { guides: GUIDES, terms: GLOSSARY }
  return {
    guides: GUIDES.filter((guide) => guideHaystack(guide).includes(needle)),
    terms: GLOSSARY.filter((term) => termHaystack(term).includes(needle)),
  }
}
