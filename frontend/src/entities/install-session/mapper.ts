import type { InstallSessionDto } from './api-types'
import type { InstallSession } from './model'

export function toInstallSession(dto: InstallSessionDto): InstallSession {
  return {
    id: dto.id,
    suiteId: dto.suiteId,
    status: dto.status,
    tier: dto.tier,
    catalogRevision: dto.catalogRevision,
    selectedApps: dto.selectedApps,
    databaseTargets: dto.databaseTargets.map((target) => ({
      catalogId: target.catalogId,
      mode: target.mode,
      server: target.server ?? null,
      name: target.name ?? null,
    })),
    disclaimerAccepted: dto.disclaimerAccepted,
    disclaimerAcceptedAt: dto.disclaimerAcceptedAt ?? null,
    orgExternalId: dto.orgExternalId ?? null,
    orgDisplayName: dto.orgDisplayName ?? null,
    installManifestId: dto.installManifestId ?? null,
    failureCode: dto.failureCode ?? null,
    createdAt: dto.createdAt,
    updatedAt: dto.updatedAt,
    completedAt: dto.completedAt ?? null,
  }
}
