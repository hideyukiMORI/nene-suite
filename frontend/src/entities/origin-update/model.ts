export type OriginUpdateStatus =
  | 'up_to_date'
  | 'update_available'
  | 'forced'
  | 'unknown'
  | 'unavailable'

export type OriginFreshness = 'fresh' | 'warn' | 'refuse_new' | 'hard'

export interface OriginUpdate {
  product: string
  channel: string
  installedVersion: string | null
  status: OriginUpdateStatus
  latestVersion: string | null
  minSupportedVersion: string | null
  changelogUrl: string | null
  releasedAt: string | null
  freshness: OriginFreshness | null
  reason: string | null
  warnings: string[]
}

export interface OriginUpdates {
  /** False when Origin is not configured (no URL / trust anchor) — show the Phase-B placeholder. */
  available: boolean
  updates: OriginUpdate[]
}

/** Statuses that count as an actionable update in the KPI (a confirmed diff). */
export const ACTIONABLE_UPDATE_STATUSES: readonly OriginUpdateStatus[] = ['update_available', 'forced']
