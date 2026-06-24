import type { MessageKey } from '@/shared/i18n'
import type { CatalogApp } from './model'

/**
 * Display state of a catalog app. Derived from the catalog status plus an
 * installed cross-reference (a Set of installed catalog ids) — no Origin
 * "update-available" state yet (Phase B). Lives in the entity so both the
 * catalog and app-detail features share one source of truth.
 */
export type CatalogCardState = 'installed' | 'installable' | 'planned' | 'deprecated'

export function deriveCardState(
  app: CatalogApp,
  installedIds: ReadonlySet<string>,
): CatalogCardState {
  if (installedIds.has(app.id)) return 'installed'
  return app.status
}

interface CardStateMeta {
  labelKey: MessageKey
  /** Status-color token for the badge. */
  tone: string
}

export const CARD_STATE_META: Record<CatalogCardState, CardStateMeta> = {
  installed: { labelKey: 'suite.home.status.active', tone: 'var(--ok)' },
  installable: { labelKey: 'suite.install.apps.status.installable', tone: 'var(--accent)' },
  planned: { labelKey: 'suite.install.apps.status.planned', tone: 'var(--fg-3)' },
  deprecated: { labelKey: 'suite.install.apps.status.deprecated', tone: 'var(--warn)' },
}

/** Filter chip order — only chips with ≥1 matching app are shown. */
export const CARD_STATE_ORDER: CatalogCardState[] = [
  'installable',
  'installed',
  'planned',
  'deprecated',
]
