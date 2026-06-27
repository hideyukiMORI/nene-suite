import { GLOSSARY } from './glossary'

/** Term id → in-page anchor (used by the glossary list and "related" links). */
export const TERM_ANCHORS: Readonly<Record<string, string>> = Object.fromEntries(
  GLOSSARY.map((term) => [term.id, `gt-${term.id}`]),
)

/** Bare identifier (as written in prose) → glossary anchor. */
const identifierAnchors: Record<string, string> = {}
for (const term of GLOSSARY) {
  for (const identifier of term.identifiers ?? []) {
    identifierAnchors[identifier] = `gt-${term.id}`
  }
}

export const IDENTIFIER_ANCHORS: Readonly<Record<string, string>> = identifierAnchors

/**
 * Identifiers sorted longest-first so greedy matching does not split a long
 * identifier (e.g. `NENE_SUITE_MODE`) into shorter ones.
 */
export const IDENTIFIERS_BY_LENGTH: readonly string[] = Object.keys(identifierAnchors).sort(
  (a, b) => b.length - a.length,
)
