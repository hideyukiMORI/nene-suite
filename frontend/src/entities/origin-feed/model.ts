export type OriginFeedKind = 'announcement' | 'ad'
export type OriginFreshness = 'fresh' | 'warn' | 'refuse_new' | 'hard'

/** A verified feed item, passed through as-is (announcement / ad shape per kind). */
export type OriginFeedItem = Record<string, unknown>

export interface OriginFeed {
  product: string
  audience: string
  kind: OriginFeedKind
  requestedLocale: string
  servedLocale: string
  available: boolean
  count: number
  items: OriginFeedItem[]
  freshness: OriginFreshness | null
  reason: string | null
  warnings: string[]
}

export interface OriginFeeds {
  /** False when Origin is not configured (no URL / trust anchor) — show the Phase-B placeholder. */
  available: boolean
  feeds: OriginFeed[]
}

export interface OriginAnnouncement {
  id: string
  severity: string
  title: string
  bodyMd: string
  linkUrl: string | null
}

export interface OriginAd {
  id: string
  title: string
  bodyMd: string
  linkUrl: string | null
  creativeUrl: string | null
}

function stringField(item: OriginFeedItem, key: string): string | null {
  const value = item[key]
  return typeof value === 'string' ? value : null
}

export function toOriginAnnouncement(item: OriginFeedItem): OriginAnnouncement {
  return {
    id: stringField(item, 'id') ?? '',
    severity: stringField(item, 'severity') ?? 'info',
    title: stringField(item, 'title') ?? '',
    bodyMd: stringField(item, 'body_md') ?? '',
    linkUrl: stringField(item, 'link_url'),
  }
}

export function toOriginAd(item: OriginFeedItem): OriginAd {
  return {
    id: stringField(item, 'id') ?? '',
    title: stringField(item, 'title') ?? '',
    bodyMd: stringField(item, 'body_md') ?? '',
    linkUrl: stringField(item, 'link_url'),
    creativeUrl: stringField(item, 'creative_url'),
  }
}
