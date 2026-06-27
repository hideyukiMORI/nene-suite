/**
 * In-app help content model.
 *
 * Help prose lives here as structured TypeScript (not i18n message keys) — the
 * volume and cross-references make a flat catalog unwieldy, and the help body is
 * Japanese-first by product decision (English follows in a later slice). See
 * docs/adr/0024-in-app-help-content-model.md.
 */

export type GlossaryCategory =
  | 'basics'
  | 'tenancy'
  | 'install'
  | 'database'
  | 'federation'
  | 'origin'
  | 'operation'

export interface GlossaryTerm {
  /** Stable slug; the glossary anchor is `gt-{id}`. */
  readonly id: string
  /** Display heading — usually the technical identifier plus a plain reading. */
  readonly term: string
  /** Kana / latin reading, included in search only. */
  readonly reading?: string
  readonly category: GlossaryCategory
  /** One-line plain-language explanation. */
  readonly short: string
  /** Optional longer paragraphs. */
  readonly body?: readonly string[]
  /** Bare identifiers in prose that should auto-link to this term (HelpText). */
  readonly identifiers?: readonly string[]
  /** Related term ids (deep links within the glossary). */
  readonly see?: readonly string[]
}

export type GuideGroup = 'start' | 'screens' | 'tutorial'

export type CalloutTone = 'danger' | 'warning' | 'neutral'

export interface GuideNote {
  readonly tone: CalloutTone
  readonly text: string
}

export interface GuideTable {
  readonly headers: readonly string[]
  readonly rows: readonly (readonly string[])[]
}

export interface GuideSection {
  readonly heading: string
  readonly paragraphs?: readonly string[]
  /** Ordered, numbered steps. */
  readonly steps?: readonly string[]
  /** Unordered bullet list. */
  readonly bullets?: readonly string[]
  /** Highlighted callouts (invariants, warnings). */
  readonly notes?: readonly GuideNote[]
  readonly table?: GuideTable
}

export interface Guide {
  /** URL slug — `/help/{slug}`. */
  readonly slug: string
  readonly title: string
  readonly group: GuideGroup
  /** One- to two-line teaser shown on the help home + index. */
  readonly summary: string
  readonly sections: readonly GuideSection[]
  /** Related guide slugs. */
  readonly related?: readonly string[]
}
