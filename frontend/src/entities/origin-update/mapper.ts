import type { OriginUpdateDto, OriginUpdateListDto } from './api-types'
import type { OriginUpdate, OriginUpdates } from './model'

function toOriginUpdate(dto: OriginUpdateDto): OriginUpdate {
  return {
    product: dto.product,
    channel: dto.channel,
    installedVersion: dto.installedVersion ?? null,
    status: dto.status,
    latestVersion: dto.latestVersion ?? null,
    minSupportedVersion: dto.minSupportedVersion ?? null,
    changelogUrl: dto.changelogUrl ?? null,
    releasedAt: dto.releasedAt ?? null,
    freshness: dto.freshness ?? null,
    reason: dto.reason ?? null,
    warnings: dto.warnings,
  }
}

export function toOriginUpdates(dto: OriginUpdateListDto): OriginUpdates {
  return {
    available: dto.available,
    updates: dto.updates.map(toOriginUpdate),
  }
}
