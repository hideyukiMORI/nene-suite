import type { OriginFeedDto, OriginFeedListDto } from './api-types'
import type { OriginFeed, OriginFeeds } from './model'

function toFeed(dto: OriginFeedDto): OriginFeed {
  return {
    product: dto.product,
    audience: dto.audience,
    kind: dto.kind,
    requestedLocale: dto.requestedLocale,
    servedLocale: dto.servedLocale,
    available: dto.available,
    count: dto.count,
    items: dto.items,
    freshness: dto.freshness ?? null,
    reason: dto.reason ?? null,
    warnings: dto.warnings,
  }
}

export function toOriginFeeds(dto: OriginFeedListDto): OriginFeeds {
  return {
    available: dto.available,
    feeds: dto.feeds.map(toFeed),
  }
}
