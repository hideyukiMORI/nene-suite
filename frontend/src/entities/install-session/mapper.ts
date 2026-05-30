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
    disclaimerAccepted: dto.disclaimerAccepted,
    disclaimerAcceptedAt: dto.disclaimerAcceptedAt,
    orgExternalId: dto.orgExternalId,
    orgDisplayName: dto.orgDisplayName,
    installManifestId: dto.installManifestId,
    failureCode: dto.failureCode,
    createdAt: dto.createdAt,
    updatedAt: dto.updatedAt,
    completedAt: dto.completedAt,
  }
}
